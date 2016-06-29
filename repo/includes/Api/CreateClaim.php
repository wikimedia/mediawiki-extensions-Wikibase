<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\ChangeOp\ChangeOpMainSnak;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for creating claims.
 *
 * @since 0.2
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class CreateClaim extends ApiBase {

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var StatementModificationHelper
	 */
	private $modificationHelper;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var EntityLoadingHelper
	 */
	private $entityLoadingHelper;

	/**
	 * @var EntitySavingHelper
	 */
	private $entitySavingHelper;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->statementChangeOpFactory = $changeOpFactoryProvider->getStatementChangeOpFactory();

		$this->modificationHelper = new StatementModificationHelper(
			$wikibaseRepo->getSnakFactory(),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getStatementGuidValidator(),
			$apiHelperFactory->getErrorReporter( $this )
		);

		$this->resultBuilder = $apiHelperFactory->getResultBuilder( $this );
		$this->entityLoadingHelper = $apiHelperFactory->getEntityLoadingHelper( $this );
		$this->entitySavingHelper = $apiHelperFactory->getEntitySavingHelper( $this );
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.2
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$entityId = $this->modificationHelper->getEntityIdFromString( $params['entity'] );
		if ( isset( $params['baserevid'] ) ) {
			$entityRevision = $this->entityLoadingHelper->loadEntityRevision(
				$entityId,
				(int)$params['baserevid']
			);
		} else {
			$entityRevision = $this->entityLoadingHelper->loadEntityRevision( $entityId );
		}
		$entity = $entityRevision->getEntity();

		$propertyId = $this->modificationHelper->getEntityIdFromString( $params['property'] );
		if ( !( $propertyId instanceof PropertyId ) ) {
			$this->errorReporter->dieError(
				$propertyId->getSerialization() . ' does not appear to be a property ID',
				'param-illegal'
			);
		}

		$snak = $this->modificationHelper->getSnakInstance( $params, $propertyId );

		$summary = $this->modificationHelper->createSummary( $params, $this );

		/* @var ChangeOpMainSnak $changeOp */
		$changeOp = $this->statementChangeOpFactory->newSetMainSnakOp( '', $snak );

		$this->modificationHelper->applyChangeOp( $changeOp, $entity, $summary );

		$statement = $entity->getStatements()->getFirstStatementWithGuid( $changeOp->getStatementGuid() );

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary, EDIT_UPDATE );
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
				$this->errorReporter->dieError(
					'A value needs to be provided when creating a claim with PropertyValueSnak snak',
					'param-missing'
				);
			} else {
				$this->errorReporter->dieError(
					'You cannot provide a value when creating a claim with no PropertyValueSnak as main snak',
					'param-illegal'
				);
			}
		}

		if ( !isset( $params['property'] ) ) {
			$this->errorReporter->dieError(
				'A property ID needs to be provided when creating a claim with a Snak',
				'param-missing'
			);
		}

		if ( isset( $params['value'] ) && json_decode( $params['value'], true ) === null ) {
			$this->errorReporter->dieError( 'Could not decode snak value', 'invalid-snak' );
		}
	}

	/**
	 * @see ApiBase::isWriteMode
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
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			array(
				'entity' => array(
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				),
				'snaktype' => array(
					self::PARAM_TYPE => array( 'value', 'novalue', 'somevalue' ),
					self::PARAM_REQUIRED => true,
				),
				'property' => array(
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => false,
				),
				'value' => array(
					self::PARAM_TYPE => 'text',
					self::PARAM_REQUIRED => false,
				),
				'summary' => array(
					self::PARAM_TYPE => 'string',
				),
				'token' => null,
				'baserevid' => array(
					self::PARAM_TYPE => 'integer',
				),
				'bot' => false,
			),
			parent::getAllowedParams()
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
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
		);
	}

}
