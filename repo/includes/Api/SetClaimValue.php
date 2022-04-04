<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\SnakFactory;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module for setting the DataValue contained by the main snak of a claim.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetClaimValue extends ApiBase {

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

		$this->logFeatureUsage( 'action=wbsetclaimvalue' );

		$guid = $params['claim'];
		$entityId = $this->guidParser->parse( $guid )->getEntityId();
		$this->validateAlteringEntityById( $entityId );
		$entity = $this->entitySavingHelper->loadEntity( $params, $entityId );

		$claim = $this->modificationHelper->getStatementFromEntity( $guid, $entity );

		$snak = $this->modificationHelper->getSnakInstance( $params, $claim->getPropertyId() );

		$summary = $this->modificationHelper->createSummary( $params, $this );

		$changeOp = $this->statementChangeOpFactory->newSetMainSnakOp( $guid, $snak );

		$this->modificationHelper->applyChangeOp( $changeOp, $entity, $summary );

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary, $params, $this->getContext() );
		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, 'pageinfo' );
		$this->resultBuilder->markSuccess();
		$this->resultBuilder->addStatement( $claim );
	}

	/**
	 * @param array $params
	 */
	private function validateParameters( array $params ): void {
		if ( !( $this->modificationHelper->validateStatementGuid( $params['claim'] ) ) ) {
			$this->errorReporter->dieError( 'Invalid claim guid', 'invalid-guid' );
		}
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
				'value' => [
					ParamValidator::PARAM_TYPE => 'text',
					ParamValidator::PARAM_REQUIRED => false,
				],
				'snaktype' => [
					ParamValidator::PARAM_TYPE => [ 'value', 'novalue', 'somevalue' ],
					ParamValidator::PARAM_REQUIRED => true,
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

		return [
			'action=wbsetclaimvalue&claim=' . $guid . '&snaktype=value'
				. '&value={"entity-type":"item","numeric-id":1}&token=foobar&baserevid=7201010'
				=> [ 'apihelp-wbsetclaimvalue-example-1', $guid ],
		];
	}

}
