<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use ApiUsageException;
use DataValues\IllegalValueException;
use Deserializers\Deserializer;
use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use IBufferingStatsdDataFactory;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParsingException;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\ClaimSummaryBuilder;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesException;
use Wikibase\Repo\SnakFactory;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module for creating or updating an entire Claim.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Addshore
 */
class SetClaim extends ApiBase {

	use FederatedPropertyApiValidatorTrait;

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @var ApiErrorReporter
	 */
	protected $errorReporter;

	/**
	 * @var Deserializer
	 */
	private $statementDeserializer;

	/**
	 * @var StatementModificationHelper
	 */
	private $modificationHelper;

	/**
	 * @var StatementGuidParser
	 */
	private $guidParser;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var EntitySavingHelper
	 */
	private $entitySavingHelper;

	/** @var IBufferingStatsdDataFactory */
	private $stats;

	/**
	 * @var string[]
	 */
	private $sandboxEntityIds;

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		ApiErrorReporter $errorReporter,
		Deserializer $statementDeserializer,
		StatementChangeOpFactory $statementChangeOpFactory,
		StatementModificationHelper $modificationHelper,
		StatementGuidParser $guidParser,
		callable $resultBuilderInstantiator,
		callable $entitySavingHelperInstantiator,
		IBufferingStatsdDataFactory $stats,
		bool $federatedPropertiesEnabled,
		array $sandboxEntityIds
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->errorReporter = $errorReporter;
		$this->statementDeserializer = $statementDeserializer;
		$this->statementChangeOpFactory = $statementChangeOpFactory;
		$this->modificationHelper = $modificationHelper;
		$this->guidParser = $guidParser;
		$this->resultBuilder = $resultBuilderInstantiator( $this );
		$this->entitySavingHelper = $entitySavingHelperInstantiator( $this );
		$this->stats = $stats;
		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
		$this->sandboxEntityIds = $sandboxEntityIds;
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		IBufferingStatsdDataFactory $stats,
		ApiHelperFactory $apiHelperFactory,
		ChangeOpFactoryProvider $changeOpFactoryProvider,
		EntityIdParser $entityIdParser,
		Deserializer $externalFormatStatementDeserializer,
		SettingsArray $repoSettings,
		SnakFactory $snakFactory,
		StatementGuidParser $statementGuidParser,
		StatementGuidValidator $statementGuidValidator
	): self {
		$modificationHelper = new StatementModificationHelper(
			$snakFactory,
			$entityIdParser,
			$statementGuidValidator,
			$apiHelperFactory->getErrorReporter( $mainModule )
		);

		return new self(
			$mainModule,
			$moduleName,
			$apiHelperFactory->getErrorReporter( $mainModule ),
			$externalFormatStatementDeserializer,
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			$modificationHelper,
			$statementGuidParser,
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getResultBuilder( $module );
			},
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getEntitySavingHelper( $module );
			},
			$stats,
			$repoSettings->getSetting( 'federatedPropertiesEnabled' ),
			$repoSettings->getSetting( 'sandboxEntityIds' )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
		try {
			$this->executeInternal();
		} catch ( FederatedPropertiesException $ex ) {
			$this->errorReporter->dieWithError(
				'wikibase-federated-properties-save-api-error-message',
				'failed-save'
			);
		}
	}

	private function executeInternal(): void {
		$params = $this->extractRequestParams();
		$statement = $this->getStatementFromParams( $params );
		$guid = $statement->getGuid();

		if ( $guid === null ) {
			$this->errorReporter->dieError( 'GUID must be set when setting a claim', 'invalid-claim' );
		}

		try {
			$statementGuid = $this->guidParser->parse( $guid );
		} catch ( StatementGuidParsingException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-claim' );
			throw new LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
		}

		$entityId = $statementGuid->getEntityId();
		$this->validateAlteringEntityById( $entityId );
		$entity = $this->entitySavingHelper->loadEntity( $params, $entityId );

		if ( !( $entity instanceof StatementListProvidingEntity ) ) {
			$this->errorReporter->dieError( 'The given entity cannot contain statements', 'not-supported' );
			throw new LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
		}

		if ( $params['ignoreduplicatemainsnak'] ) {
			if ( $this->statementMainSnakAlreadyExists( $statement, $entity->getStatements() ) ) {
				$this->addWarning( 'wikibase-setclaim-warning-duplicatemainsnak' );
				return;
			}
		}
		$summary = $this->getSummary( $params, $statement, $entity->getStatements() );

		$index = $params['index'] ?? null;
		$changeop = $this->statementChangeOpFactory->newSetStatementOp( $statement, $index );
		$this->modificationHelper->applyChangeOp( $changeop, $entity, $summary );
		$statement = $entity->getStatements()->getFirstStatementWithGuid( $guid );

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary, $params, $this->getContext() );
		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, 'pageinfo' );
		$this->resultBuilder->markSuccess();
		$this->resultBuilder->addStatement( $statement );

		$this->stats->increment( 'wikibase.repo.api.wbsetclaim.total' );
		if ( $index !== null ) {
			$this->stats->increment( 'wikibase.repo.api.wbsetclaim.index' );
		}
	}

	private function statementMainSnakAlreadyExists(
		Statement $statement,
		StatementList $existingStatements
	): bool {
		$propertyId = $statement->getPropertyId();
		$mainSnak = $statement->getMainSnak();
		foreach ( $existingStatements as $existingStatement ) {
			if ( $existingStatement->getPropertyId()->equals( $propertyId ) ) {
				if ( $existingStatement->getMainSnak()->equals( $mainSnak ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @param array $params
	 * @param Statement $statement
	 * @param StatementList $statementList
	 *
	 * @throws InvalidArgumentException
	 * @return Summary
	 *
	 * @todo this summary builder is ugly and summary stuff needs to be refactored
	 */
	private function getSummary( array $params, Statement $statement, StatementList $statementList ): Summary {
		$claimSummaryBuilder = new ClaimSummaryBuilder(
			$this->getModuleName(),
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) )
		);

		$summary = $claimSummaryBuilder->buildClaimSummary(
			$statementList->getFirstStatementWithGuid( $statement->getGuid() ),
			$statement
		);

		if ( isset( $params['summary'] ) ) {
			$summary->setUserSummary( $params['summary'] );
		}

		return $summary;
	}

	/**
	 * @param array $params
	 *
	 * @throws IllegalValueException
	 * @throws ApiUsageException
	 * @throws LogicException
	 * @return Statement
	 */
	private function getStatementFromParams( array $params ): Statement {
		try {
			$serializedStatement = json_decode( $params['claim'], true );
			if ( !is_array( $serializedStatement ) ) {
				throw new IllegalValueException( 'Failed to get statement from Serialization' );
			}
			$statement = $this->statementDeserializer->deserialize( $serializedStatement );
			if ( !( $statement instanceof Statement ) ) {
				throw new IllegalValueException( 'Failed to get statement from Serialization' );
			}
			return $statement;
		} catch ( InvalidArgumentException $invalidArgumentException ) {
			$this->errorReporter->dieError(
				'Failed to get claim from claim Serialization ' . $invalidArgumentException->getMessage(),
				'invalid-claim'
			);
		} catch ( OutOfBoundsException $outOfBoundsException ) {
			$this->errorReporter->dieError(
				'Failed to get claim from claim Serialization ' . $outOfBoundsException->getMessage(),
				'invalid-claim'
			);
		}

		// Note: since dieError() never returns, this should be unreachable!
		throw new LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
	}

	/**
	 * @inheritDoc
	 */
	public function isWriteMode(): bool {
		return true;
	}

	/**
	 * @see ApiBase::needsToken
	 *
	 * @return string
	 */
	public function needsToken(): string {
		return 'csrf';
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return array_merge(
			[
				'claim' => [
					ParamValidator::PARAM_TYPE => 'text',
					ParamValidator::PARAM_REQUIRED => true,
				],
				'index' => [
					ParamValidator::PARAM_TYPE => 'integer',
				],
				'summary' => [
					ParamValidator::PARAM_TYPE => 'string',
				],
				'tags' => [
					ParamValidator::PARAM_TYPE => 'tags',
					ParamValidator::PARAM_ISMULTI => true,
				],
				'token' => null,
				'baserevid' => [
					ParamValidator::PARAM_TYPE => 'integer',
				],
				'bot' => false,
				'ignoreduplicatemainsnak' => false,
			],
			parent::getAllowedParams()
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		$guid = $this->sandboxEntityIds[ 'mainItem' ] . '$5627445f-43cb-ed6d-3adb-760e85bd17ee';

		return [
			'action=wbsetclaim&claim={"id":"' . $guid . '",'
				. '"type":"claim","mainsnak":{"snaktype":"value","property":"P1",'
				. '"datavalue":{"value":"City","type":"string"}}}'
				=> 'apihelp-wbsetclaim-example-1',
			'action=wbsetclaim&claim={"id":"' . $guid . '",'
				. '"type":"claim","mainsnak":{"snaktype":"value","property":"P1",'
				. '"datavalue":{"value":"City","type":"string"}}}&index=0'
				=> 'apihelp-wbsetclaim-example-2',
			'action=wbsetclaim&claim={"id":"' . $guid . '",'
				. '"type":"statement","mainsnak":{"snaktype":"value","property":"P1",'
				. '"datavalue":{"value":"City","type":"string"}},'
				. '"references":[{"snaks":{"P2":[{"snaktype":"value","property":"P2",'
				. '"datavalue":{"value":"The Economy of Cities","type":"string"}}]},'
				. '"snaks-order":["P2"]}],"rank":"normal"}'
				=> 'apihelp-wbsetclaim-example-3',
		];
	}

}
