<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\ChangeOpMainSnak;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\SnakFactory;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module for creating claims.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class CreateClaim extends ApiBase {

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
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var EntitySavingHelper
	 */
	private $entitySavingHelper;

	/**
	 * @var array
	 */
	private $sandboxEntityIds;

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		StatementChangeOpFactory $statementChangeOpFactory,
		ApiErrorReporter $errorReporter,
		StatementModificationHelper $modificationHelper,
		callable $resultBuilderInstantiator,
		callable $entitySavingHelperInstantiator,
		bool $federatedPropertiesEnabled,
		array $sandboxEntityIds
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->statementChangeOpFactory = $statementChangeOpFactory;
		$this->errorReporter = $errorReporter;
		$this->modificationHelper = $modificationHelper;
		$this->resultBuilder = $resultBuilderInstantiator( $this );
		$this->entitySavingHelper = $entitySavingHelperInstantiator( $this );
		$this->entitySavingHelper->setEntityIdParam( 'entity' );
		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
		$this->sandboxEntityIds = $sandboxEntityIds;
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ApiHelperFactory $apiHelperFactory,
		ChangeOpFactoryProvider $changeOpFactoryProvider,
		EntityIdParser $entityIdParser,
		SettingsArray $settings,
		SnakFactory $snakFactory,
		StatementGuidValidator $statementGuidValidator
	): self {
		$errorReporter = $apiHelperFactory->getErrorReporter( $mainModule );

		$modificationHelper = new StatementModificationHelper(
			$snakFactory,
			$entityIdParser,
			$statementGuidValidator,
			$errorReporter
		);

		return new self(
			$mainModule,
			$moduleName,
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			$errorReporter,
			$modificationHelper,
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getResultBuilder( $module );
			},
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getEntitySavingHelper( $module );
			},
			$settings->getSetting( 'federatedPropertiesEnabled' ),
			$settings->getSetting( 'sandboxEntityIds' )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$entityId = $this->entitySavingHelper->getEntityIdFromParams( $params );
		$this->validateAlteringEntityById( $entityId );

		$entity = $this->entitySavingHelper->loadEntity( $params, $entityId );

		$propertyId = $this->modificationHelper->getEntityIdFromString( $params['property'] );
		if ( !( $propertyId instanceof PropertyId ) ) {
			$this->errorReporter->dieWithError(
				'wikibase-api-invalid-property-id',
				'param-illegal'
			);
		}

		$snak = $this->modificationHelper->getSnakInstance( $params, $propertyId );

		$summary = $this->modificationHelper->createSummary( $params, $this );

		/** @var ChangeOpMainSnak $changeOp */
		$changeOp = $this->statementChangeOpFactory->newSetMainSnakOp( '', $snak );

		$this->modificationHelper->applyChangeOp( $changeOp, $entity, $summary );

		// @phan-suppress-next-line PhanUndeclaredMethod
		$statement = $entity->getStatements()->getFirstStatementWithGuid( $changeOp->getStatementGuid() );

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary, $params, $this->getContext() );
		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, 'pageinfo' );
		$this->resultBuilder->markSuccess();
		$this->resultBuilder->addStatement( $statement );
	}

	/**
	 * Checks if the required parameters are set and the ones that make no sense given the
	 * snaktype value are not set.
	 *
	 * @param array $params
	 */
	private function validateParameters( array $params ): void {
		if ( $params['snaktype'] === 'value' xor isset( $params['value'] ) ) {
			if ( $params['snaktype'] === 'value' ) {
				$this->errorReporter->dieWithError(
					[ 'wikibase-api-claim-value-missing' ],
					'param-missing'
				);
			} else {
				$this->errorReporter->dieWithError(
					[ 'wikibase-api-claim-value-unexpected' ],
					'param-illegal'
				);
			}
		}

		if ( !isset( $params['property'] ) ) {
			$this->errorReporter->dieWithError(
				[ 'wikibase-api-param-missing', 'property' ],
				'param-missing'
			);
		}

		if ( isset( $params['value'] ) && json_decode( $params['value'], true ) === null ) {
			$this->errorReporter->dieWithError(
				[ 'wikibase-api-invalid-snak' ],
				'invalid-snak'
			);
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
				'entity' => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => true,
				],
				'snaktype' => [
					ParamValidator::PARAM_TYPE => [ 'value', 'novalue', 'somevalue' ],
					ParamValidator::PARAM_REQUIRED => true,
				],
				'property' => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => true,
				],
				'value' => [
					ParamValidator::PARAM_TYPE => 'text',
					ParamValidator::PARAM_REQUIRED => false,
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
		$id = $this->sandboxEntityIds['mainItem'];

		return [
			'action=wbcreateclaim&entity=' . $id . '&property=P9001&snaktype=novalue'
				=> [ 'apihelp-wbcreateclaim-example-1', $id ],
			'action=wbcreateclaim&entity=' . $id . '&property=P9002&snaktype=value&value="itsastring"'
				=> [ 'apihelp-wbcreateclaim-example-2', $id ],
			'action=wbcreateclaim&entity=' . $id . '&property=P9003&snaktype=value&value='
				. '{"entity-type":"item","numeric-id":1}'
				=> [ 'apihelp-wbcreateclaim-example-3', $id ],
			'action=wbcreateclaim&entity=' . $id . '&property=P9004&snaktype=value&value='
				. '{"latitude":40.748433,"longitude":-73.985656,'
				. '"globe":"http://www.wikidata.org/entity/Q2","precision":0.000001}'
				=> [ 'apihelp-wbcreateclaim-example-4', $id ],
		];
	}

}
