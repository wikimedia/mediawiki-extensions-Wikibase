<?php

namespace Wikibase\Api;

use ApiBase;
use Wikibase\ChangeOp\ChangeOpMainSnak;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\Lib\ClaimGuidGenerator;

/**
 * API module for setting the DataValue contained by the main snak of a claim.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetClaimValue extends ModifyClaim {

	/**
	 * @see \ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$claimGuid = $params['claim'];
		$entityId = $this->claimGuidParser->parse( $claimGuid )->getEntityId();
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$entityRevision = $this->loadEntityRevision( $entityId, $baseRevisionId );
		$entity = $entityRevision->getEntity();

		$claim = $this->claimModificationHelper->getClaimFromEntity( $claimGuid, $entity );

		$snak = $this->claimModificationHelper->getSnakInstance( $params, $claim->getMainSnak()->getPropertyId() );

		$summary = $this->claimModificationHelper->createSummary( $params, $this );

		$guidGenerator = new ClaimGuidGenerator( $entity->getId() );
		$changeOp = new ChangeOpMainSnak(
			$claimGuid,
			$snak,
			$guidGenerator
		);

		try {
			$changeOp->apply( $entity, $summary );
		} catch ( ChangeOpException $e ) {
			$this->dieUsage( $e->getMessage(), 'failed-save' );
		}

		$this->saveChanges( $entity, $summary );
		$this->getResultBuilder()->markSuccess();
		$this->getResultBuilder()->addClaim( $claim );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		if ( !( $this->claimModificationHelper->validateClaimGuid( $params['claim'] ) ) ) {
			$this->dieUsage( 'Invalid claim guid' , 'invalid-guid' );
		}
	}

	/**
	 * @see ApiBase::getAllowedParams
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array_merge(
			array(
				'claim' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => true,
				),
				'value' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => false,
				),
				'snaktype' => array(
					ApiBase::PARAM_TYPE => array( 'value', 'novalue', 'somevalue' ),
					ApiBase::PARAM_REQUIRED => true,
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
				'claim' => 'A GUID identifying the claim',
				'snaktype' => 'The type of the snak',
				'value' => 'The value to set the datavalue of the the main snak of the claim to',
			)
		);
	}

	/**
	 * @see \ApiBase::getDescription
	 */
	public function getDescription() {
		return array(
			'API module for setting the value of a Wikibase claim.'
		);
	}

	/**
	 * @see \ApiBase::getExamples
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetclaimvalue&claim=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F&snaktype=value&value={"entity-type":"item","numeric-id":1}&token=foobar&baserevid=7201010' => 'Sets the claim with the GUID of Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F to a value of Q1',
		);
	}
}
