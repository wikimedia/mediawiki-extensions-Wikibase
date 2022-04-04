<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\SnakFactory;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module for removing one or more references of the same statement.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class RemoveReferences extends ApiBase {

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

	/**
	 * @var string[]
	 */
	private $sandboxEntityIds;

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		ApiErrorReporter $errorReporter,
		StatementChangeOpFactory $statementChangeOpFactory,
		StatementModificationHelper $modificationHelper,
		StatementGuidParser $guidParser,
		callable $resultBuilderInstantiator,
		callable $entitySavingHelperInstantiator,
		bool $federatedPropertiesEnabled,
		array $sandboxEntityIds
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->errorReporter = $errorReporter;
		$this->statementChangeOpFactory = $statementChangeOpFactory;
		$this->modificationHelper = $modificationHelper;
		$this->guidParser = $guidParser;
		$this->resultBuilder = $resultBuilderInstantiator( $this );
		$this->entitySavingHelper = $entitySavingHelperInstantiator( $this );
		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
		$this->sandboxEntityIds = $sandboxEntityIds;
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ApiHelperFactory $apiHelperFactory,
		ChangeOpFactoryProvider $changeOpFactoryProvider,
		EntityIdParser $entityIdParser,
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
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			$modificationHelper,
			$statementGuidParser,
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getResultBuilder( $module );
			},
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getEntitySavingHelper( $module );
			},
			$repoSettings->getSetting( 'federatedPropertiesEnabled' ),
			$repoSettings->getSetting( 'sandboxEntityIds' )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$guid = $params['statement'];
		$entityId = $this->guidParser->parse( $guid )->getEntityId();

		$this->validateAlteringEntityById( $entityId );

		$entity = $this->entitySavingHelper->loadEntity( $params, $entityId );
		$summary = $this->modificationHelper->createSummary( $params, $this );

		$claim = $this->modificationHelper->getStatementFromEntity( $guid, $entity );

		if ( !( $claim instanceof Statement ) ) {
			$this->errorReporter->dieError(
				'The referenced claim is not a statement and thus cannot have references',
				'not-statement'
			);
		}

		$referenceHashes = $this->getReferenceHashesFromParams( $params, $claim );

		$changeOps = new ChangeOps();
		$changeOps->add( $this->getChangeOps( $guid, $referenceHashes ) );

		try {
			$changeOps->apply( $entity, $summary );
		} catch ( ChangeOpException $e ) {
			$this->errorReporter->dieException( $e, 'failed-save' );
		}

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary, $params, $this->getContext() );
		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, 'pageinfo' );
		$this->resultBuilder->markSuccess();
	}

	private function validateParameters( array $params ): void {
		if ( !( $this->modificationHelper->validateStatementGuid( $params['statement'] ) ) ) {
			$this->errorReporter->dieError( 'Invalid claim guid', 'invalid-guid' );
		}
	}

	/**
	 * @param string $guid
	 * @param string[] $referenceHashes
	 *
	 * @return ChangeOp[]
	 */
	private function getChangeOps( string $guid, array $referenceHashes ): array {
		$changeOps = [];

		foreach ( $referenceHashes as $referenceHash ) {
			$changeOps[] = $this->statementChangeOpFactory->newRemoveReferenceOp( $guid, $referenceHash );
		}

		return $changeOps;
	}

	/**
	 * @param array $params
	 * @param Statement $statement
	 *
	 * @return string[]
	 */
	private function getReferenceHashesFromParams( array $params, Statement $statement ): array {
		$references = $statement->getReferences();
		$hashes = [];

		foreach ( array_unique( $params['references'] ) as $referenceHash ) {
			if ( !$references->hasReferenceHash( $referenceHash ) ) {
				$this->errorReporter->dieError( 'Invalid reference hash', 'no-such-reference' );
			}
			$hashes[] = $referenceHash;
		}

		return $hashes;
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
				'statement' => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => true,
				],
				'references' => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => true,
					ParamValidator::PARAM_ISMULTI => true,
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
			],
			parent::getAllowedParams()
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		$guid = $this->sandboxEntityIds[ 'mainItem' ] . '$D8404CDA-25E4-4334-AF13-A3290BCD9C0F';
		$hash = '455481eeac76e6a8af71a6b493c073d54788e7e9';

		return [
			'action=wbremovereferences&statement=' . $guid
				. '&references=' . $hash . '&token=foobar'
				. '&baserevid=7201010'
				=> [ 'apihelp-wbremovereferences-example-1', $hash, $guid ],
		];
	}

}
