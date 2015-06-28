<?php

namespace Wikibase\Api;

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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class CreateClaim extends ModifyClaim {

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$changeOpFactoryProvider = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider();
		$this->statementChangeOpFactory = $changeOpFactoryProvider->getStatementChangeOpFactory();
	}

	/**
	 * @see \ApiBase::execute
	 *
	 * @since 0.2
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$entityId = $this->modificationHelper->getEntityIdFromString( $params['entity'] );
		$baseRevisionId = isset( $params['baserevid'] ) ? (int)$params['baserevid'] : null;
		$entityRevision = $this->loadEntityRevision( $entityId, $baseRevisionId );
		$entity = $entityRevision->getEntity();

		$propertyId = $this->modificationHelper->getEntityIdFromString( $params['property'] );
		if ( !$propertyId instanceof PropertyId ) {
			$this->dieError(
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

		$this->saveChanges( $entity, $summary );
		$this->getResultBuilder()->markSuccess();
		$this->getResultBuilder()->addClaim( $statement );
	}

	/**
	 * Checks if the required parameters are set and the ones that make no sense given the
	 * snaktype value are not set.
	 *
	 * @params array $params
	 */
	private function validateParameters( array $params ) {
		if ( $params['snaktype'] === 'value' XOR isset( $params['value'] ) ) {
			if ( $params['snaktype'] === 'value' ) {
				$this->dieError( 'A value needs to be provided when creating a claim with PropertyValueSnak snak', 'param-missing' );
			} else {
				$this->dieError( 'You cannot provide a value when creating a claim with no PropertyValueSnak as main snak', 'param-illegal' );
			}
		}

		if ( !isset( $params['property'] ) ) {
			$this->dieError( 'A property ID needs to be provided when creating a claim with a Snak', 'param-missing' );
		}

		if ( isset( $params['value'] ) && json_decode( $params['value'], true ) === null ) {
			$this->dieError( 'Could not decode snak value', 'invalid-snak' );
		}
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			array(
				'entity' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => true,
				),
				'snaktype' => array(
					ApiBase::PARAM_TYPE => array( 'value', 'novalue', 'somevalue' ),
					ApiBase::PARAM_REQUIRED => true,
				),
				'property' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => false,
				),
				'value' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => false,
				),
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
				=>'apihelp-wbcreateclaim-example-1',
			'action=wbcreateclaim&entity=Q42&property=P9002&snaktype=value&value="itsastring"'
				=> 'apihelp-wbcreateclaim-example-2',
			'action=wbcreateclaim&entity=Q42&property=P9003&snaktype=value&value={"entity-type":"item","numeric-id":1}'
				=> 'apihelp-wbcreateclaim-example-3',
			'action=wbcreateclaim&entity=Q42&property=P9004&snaktype=value&value={"latitude":40.748433,"longitude":-73.985656,"globe":"http://www.wikidata.org/entity/Q2","precision":0.000001}'
				=> 'apihelp-wbcreateclaim-example-4',
		);
	}

}
