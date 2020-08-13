<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\ChangeOp\ChangeOpMainSnak;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;

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

	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		StatementChangeOpFactory $statementChangeOpFactory,
		ApiErrorReporter $errorReporter,
		StatementModificationHelper $modificationHelper,
		callable $resultBuilderInstantiator,
		callable $entitySavingHelperInstantiator,
		bool $federatedPropertiesEnabled
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->statementChangeOpFactory = $statementChangeOpFactory;
		$this->errorReporter = $errorReporter;
		$this->modificationHelper = $modificationHelper;
		$this->resultBuilder = $resultBuilderInstantiator( $this );
		$this->entitySavingHelper = $entitySavingHelperInstantiator( $this );
		$this->entitySavingHelper->setEntityIdParam( 'entity' );
		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$entityId = $this->entitySavingHelper->getEntityIdFromParams( $params );
		$this->validateAlteringEntityById( $entityId );

		$entity = $this->entitySavingHelper->loadEntity( $entityId );

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

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary );
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
	private function validateParameters( array $params ) {
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
	public function isWriteMode() {
		return true;
	}

	/**
	 * @see ApiBase::needsToken
	 *
	 * @return string
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams() {
		return array_merge(
			[
				'entity' => [
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				],
				'snaktype' => [
					self::PARAM_TYPE => [ 'value', 'novalue', 'somevalue' ],
					self::PARAM_REQUIRED => true,
				],
				'property' => [
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				],
				'value' => [
					self::PARAM_TYPE => 'text',
					self::PARAM_REQUIRED => false,
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
	protected function getExamplesMessages() {
		return [
			'action=wbcreateclaim&entity=Q42&property=P9001&snaktype=novalue'
				=> 'apihelp-wbcreateclaim-example-1',
			'action=wbcreateclaim&entity=Q42&property=P9002&snaktype=value&value="itsastring"'
				=> 'apihelp-wbcreateclaim-example-2',
			'action=wbcreateclaim&entity=Q42&property=P9003&snaktype=value&value='
				. '{"entity-type":"item","numeric-id":1}'
				=> 'apihelp-wbcreateclaim-example-3',
			'action=wbcreateclaim&entity=Q42&property=P9004&snaktype=value&value='
				. '{"latitude":40.748433,"longitude":-73.985656,'
				. '"globe":"http://www.wikidata.org/entity/Q2","precision":0.000001}'
				=> 'apihelp-wbcreateclaim-example-4',
		];
	}

}
