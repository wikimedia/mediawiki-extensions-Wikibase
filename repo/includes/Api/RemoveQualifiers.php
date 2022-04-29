<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use ApiUsageException;
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
 * API module for removing qualifiers from a statement.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class RemoveQualifiers extends ApiBase {

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

		$guid = $params['claim'];
		$entityId = $this->guidParser->parse( $guid )->getEntityId();

		$this->validateAlteringEntityById( $entityId );

		$entity = $this->entitySavingHelper->loadEntity( $params, $entityId );

		$summary = $this->modificationHelper->createSummary( $params, $this );

		$statement = $this->modificationHelper->getStatementFromEntity( $guid, $entity );
		$qualifierHashes = $this->getQualifierHashesFromParams( $params, $statement );

		$changeOps = new ChangeOps();
		$changeOps->add( $this->getChangeOps( $guid, $qualifierHashes ) );

		try {
			$changeOps->apply( $entity, $summary );
		} catch ( ChangeOpException $e ) {
			$this->errorReporter->dieException( $e, 'failed-save' );
		}

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary, $params, $this->getContext() );
		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, 'pageinfo' );
		$this->resultBuilder->markSuccess();
	}

	/**
	 * @param array $params
	 *
	 * @throws ApiUsageException
	 */
	private function validateParameters( array $params ): void {
		if ( !( $this->modificationHelper->validateStatementGuid( $params['claim'] ) ) ) {
			$this->errorReporter->dieError( 'Invalid claim guid', 'invalid-guid' );
		}
	}

	/**
	 * @param string $statementGuid
	 * @param string[] $qualifierHashes
	 *
	 * @return ChangeOp[]
	 */
	private function getChangeOps( string $statementGuid, array $qualifierHashes ): array {
		$changeOps = [];

		foreach ( $qualifierHashes as $hash ) {
			$changeOps[] = $this->statementChangeOpFactory->newRemoveQualifierOp(
				$statementGuid,
				$hash
			);
		}

		return $changeOps;
	}

	/**
	 * @param array $params
	 * @param Statement $statement
	 *
	 * @return string[]
	 */
	private function getQualifierHashesFromParams( array $params, Statement $statement ): array {
		$qualifiers = $statement->getQualifiers();
		$hashes = [];

		foreach ( array_unique( $params['qualifiers'] ) as $qualifierHash ) {
			if ( !$qualifiers->hasSnakHash( $qualifierHash ) ) {
				$this->errorReporter->dieError( 'Invalid snak hash', 'no-such-qualifier' );
			}

			$hashes[] = $qualifierHash;
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
				'claim' => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => true,
				],
				'qualifiers' => [
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
		$hash = '1eb8793c002b1d9820c833d234a1b54c8e94187e';

		return [
			'action=wbremovequalifiers&claim=' . $guid
				. '&qualifiers=' . $hash . '&token=foobar'
				. '&baserevid=7201010'
				=> [ 'apihelp-wbremovequalifiers-example-1', $hash, $guid ],
		];
	}

}
