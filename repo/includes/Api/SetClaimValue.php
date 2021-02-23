<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\WikibaseRepo;

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

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		ApiErrorReporter $errorReporter,
		StatementChangeOpFactory $statementChangeOpFactory,
		StatementModificationHelper $modificationHelper,
		StatementGuidParser $guidParser,
		callable $resultBuilderInstantiator,
		callable $entitySavingHelperInstantiator,
		bool $federatedPropertiesEnabled
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->errorReporter = $errorReporter;
		$this->statementChangeOpFactory = $statementChangeOpFactory;
		$this->modificationHelper = $modificationHelper;
		$this->guidParser = $guidParser;
		$this->resultBuilder = $resultBuilderInstantiator( $this );
		$this->entitySavingHelper = $entitySavingHelperInstantiator( $this );
		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		EntityIdParser $entityIdParser,
		StatementGuidParser $statementGuidParser,
		StatementGuidValidator $statementGuidValidator
	): self {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

		$modificationHelper = new StatementModificationHelper(
			$wikibaseRepo->getSnakFactory(),
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
			$wikibaseRepo->inFederatedPropertyMode()
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
		$entity = $this->entitySavingHelper->loadEntity( $entityId );

		$claim = $this->modificationHelper->getStatementFromEntity( $guid, $entity );

		$snak = $this->modificationHelper->getSnakInstance( $params, $claim->getPropertyId() );

		$summary = $this->modificationHelper->createSummary( $params, $this );

		$changeOp = $this->statementChangeOpFactory->newSetMainSnakOp( $guid, $snak );

		$this->modificationHelper->applyChangeOp( $changeOp, $entity, $summary );

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary );
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
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				],
				'value' => [
					self::PARAM_TYPE => 'text',
					self::PARAM_REQUIRED => false,
				],
				'snaktype' => [
					self::PARAM_TYPE => [ 'value', 'novalue', 'somevalue' ],
					self::PARAM_REQUIRED => true,
				],
				'summary' => [
					self::PARAM_TYPE => 'string',
				],
				'tags' => [
					self::PARAM_TYPE => 'tags',
					self::PARAM_ISMULTI => true,
				],
				'token' => null,
				'baserevid' => [
					self::PARAM_TYPE => 'integer',
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
		return [
			'action=wbsetclaimvalue&claim=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F&snaktype=value'
				. '&value={"entity-type":"item","numeric-id":1}&token=foobar&baserevid=7201010'
				=> 'apihelp-wbsetclaimvalue-example-1',
		];
	}

}
