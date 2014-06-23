<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Wikibase\ChangeOp\ChangeOpMainSnak;
use Wikibase\ChangeOp\ClaimChangeOpFactory;
use Wikibase\DataModel\Claim\Claims;
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
	 * @var ClaimChangeOpFactory
	 */
	protected $claimChangeOpFactory;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$changeOpFactoryProvider = WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider();
		$this->claimChangeOpFactory = $changeOpFactoryProvider->getClaimChangeOpFactory();
	}

	/**
	 * @see \ApiBase::execute
	 *
	 * @since 0.2
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$entityId = $this->claimModificationHelper->getEntityIdFromString( $params['entity'] );
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$entityRevision = $this->loadEntityRevision( $entityId, $baseRevisionId );
		$entity = $entityRevision->getEntity();

		$propertyId = $this->claimModificationHelper->getEntityIdFromString( $params['property'] );
		if( !$propertyId instanceof PropertyId ){
			$this->dieUsage(
				$propertyId->getSerialization() . ' does not appear to be a property ID',
				'param-illegal'
			);
		}

		$snak = $this->claimModificationHelper->getSnakInstance( $params, $propertyId );

		$summary = $this->claimModificationHelper->createSummary( $params, $this );

		/* @var ChangeOpMainSnak $changeOp */
		$changeOp = $this->claimChangeOpFactory->newSetMainSnakOp( '', $snak );

		$this->claimModificationHelper->applyChangeOp( $changeOp, $entity, $summary );

		$claims = new Claims( $entity->getClaims() );
		$claim = $claims->getClaimWithGuid( $changeOp->getClaimGuid() );

		$this->saveChanges( $entity, $summary );
		$this->getResultBuilder()->markSuccess();
		$this->getResultBuilder()->addClaim( $claim );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Checks if the required parameters are set and the ones that make no sense given the
	 * snaktype value are not set.
	 *
	 * @since 0.2
	 *
	 * @params array $params
	 */
	protected function validateParameters( array $params ) {
		if ( $params['snaktype'] == 'value' XOR isset( $params['value'] ) ) {
			if ( $params['snaktype'] == 'value' ) {
				$this->dieUsage( 'A value needs to be provided when creating a claim with PropertyValueSnak snak', 'param-missing' );
			}
			else {
				$this->dieUsage( 'You cannot provide a value when creating a claim with no PropertyValueSnak as main snak', 'param-illegal' );
			}
		}

		if ( !isset( $params['property'] ) ) {
			$this->dieUsage( 'A property ID needs to be provided when creating a claim with a Snak', 'param-missing' );
		}

		if ( isset( $params['value'] ) && \FormatJson::decode( $params['value'], true ) == null ) {
			$this->dieUsage( 'Could not decode snak value', 'invalid-snak' );
		}
	}

	/**
	 * @see \ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge(
			parent::getPossibleErrors(),
			array(
				array( 'code' => 'param-missing', 'info' => $this->msg( 'wikibase-api-param-missing' )->text() ),
				array( 'code' => 'param-illegal', 'info' => $this->msg( 'wikibase-api-param-illegal' )->text() ),
			)
		);
	}

	/**
	 * @see \ApiBase::getAllowedParams
	 */
	public function getAllowedParams() {
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
	 * @see \ApiBase::getParamDescription
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			array(
				'entity' => 'Id of the entity you are adding the claim to',
				'property' => 'Id of the snaks property',
				'value' => 'Value of the snak when creating a claim with a snak that has a value',
				'snaktype' => 'The type of the snak',
			)
		);
	}

	/**
	 * @see \ApiBase::getDescription
	 */
	public function getDescription() {
		return array(
			'API module for creating Wikibase claims.'
		);
	}

	/**
	 * @see \ApiBase::getExamples
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbcreateclaim&entity=Q42&property=P9001&snaktype=novalue'
				=>'Creates a claim for item Q42 of property P9001 with a novalue snak.',
			'api.php?action=wbcreateclaim&entity=Q42&property=P9002&snaktype=value&value="itsastring"'
				=> ' Creates a claim for item Q42 of property P9002 with string value "itsastring"',
			'api.php?action=wbcreateclaim&entity=Q42&property=P9003&snaktype=value&value={"entity-type":"item","numeric-id":1}'
				=> 'Creates a claim for item Q42 of property P9003 with a value of item Q1',
			'api.php?action=wbcreateclaim&entity=Q42&property=P9004&snaktype=value&value={"latitude":40.748433,"longitude":-73.985656,"globe":"http://www.wikidata.org/entity/Q2","precision":0.000001}'
				=> 'Creates a claim for item Q42 of property P9004 with a coordinate snak value',
		);
	}
}
