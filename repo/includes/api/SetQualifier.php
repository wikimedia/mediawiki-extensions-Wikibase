<?php

namespace Wikibase\Api;

use ApiBase;
use Wikibase\Entity;
use Wikibase\Claim;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\ChangeOpQualifier;
use Wikibase\ChangeOpException;

/**
 * API module for creating a qualifier or setting the value of an existing one.
 *
 * @since 0.3
 *
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetQualifier extends ModifyClaim {

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$entityId = $this->claimModificationHelper->getEntityIdFromString(
			Entity::getIdFromClaimGuid( $params['claim'] )
		);
		$entityTitle = $this->claimModificationHelper->getEntityTitle( $entityId );
		$entityContent = $this->getEntityContent( $entityTitle );
		$entity = $entityContent->getEntity();
		$summary = $this->claimModificationHelper->createSummary( $params, $this );

		$claim = $this->claimModificationHelper->getClaimFromEntity( $params['claim'], $entity );

		if ( isset( $params['snakhash'] ) ) {
			$this->validateReferenceHash( $claim, $params['snakhash'] );
		}

		$changeOp = $this->getChangeOp();

		try {
			$changeOp->apply( $entity, $summary );
		} catch ( ChangeOpException $e ) {
			$this->dieUsage( $e->getMessage(), 'failed-save' );
		}

		$this->saveChanges( $entityContent, $summary );

		$this->claimModificationHelper->addClaimToApiResult( $claim );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Checks if the required parameters are set and the ones that make no sense given the
	 * snaktype value are not set.
	 *
	 * @since 0.2
	 */
	protected function validateParameters( array $params ) {
		if ( !( $this->claimModificationHelper->validateClaimGuid( $params['claim'] ) ) ) {
			$this->dieUsage( 'Invalid claim guid' , 'invalid-guid' );
		}
		//@todo addshore all of these errors should be more general
		if ( !isset( $params['snakhash'] ) ) {
			if ( !isset( $params['snaktype'] ) ) {
				$this->dieUsage( 'When creating a new qualifier (ie when not providing a snakhash) a snaktype should be specified', 'param-missing' );
			}

			if ( !isset( $params['property'] ) ) {
				$this->dieUsage( 'When creating a new qualifier (ie when not providing a snakhash) a property should be specified', 'param-missing' );
			}
		}

		if ( isset( $params['snaktype'] ) && $params['snaktype'] === 'value' && !isset( $params['value'] ) ) {
			$this->dieUsage( 'When setting a qualifier that is a PropertyValueSnak, the value needs to be provided', 'param-missing' );
		}
	}

	/**
	 * @since 0.4
	 *
	 * @param Claim $claim
	 * @param string $qualifierHash
	 */
	protected function validateQualifierHash( Claim $claim, $qualifierHash ) {
		if ( !$claim->getReferences()->hasReferenceHash( $qualifierHash ) ) {
			$this->dieUsage( "Claim does not have a qualifier with the given hash" , 'no-such-reference' );
		}
	}

	/**
	 * @since 0.4
	 *
	 * @return ChangeOpQualifier
	 */
	protected function getChangeOp() {
		$params = $this->extractRequestParams();

		$claimGuid = $params['claim'];
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();

		if ( isset( $params['snakhash'] ) ) {
			$propertyId = $this->claimModificationHelper->getEntityIdFromString( $params['property'] );
			$newQualifier = $this->claimModificationHelper->getSnakInstance( $params, $propertyId );
			$changeOp = new ChangeOpQualifier( $claimGuid, $newQualifier, $params['snakhash'], $idFormatter );
		} else {
			$propertyId = $this->claimModificationHelper->getEntityIdFromString( $params['property'] );
			$newQualifier = $this->claimModificationHelper->getSnakInstance( $params, $propertyId );
			$changeOp = new ChangeOpQualifier( $claimGuid, $newQualifier, '', $idFormatter );
		}

		return $changeOp;
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
			parent::getAllowedParams(),
			array(
				'claim' => array(
					ApiBase::PARAM_TYPE => 'string',
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
				'snaktype' => array(
					ApiBase::PARAM_TYPE => array( 'value', 'novalue', 'somevalue' ),
					ApiBase::PARAM_REQUIRED => false,
				),
				'snakhash' => array(
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => false,
				),
			)
		);
	}

	/**
	 * @see ApiBase::getParamDescription
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			array(
				'claim' => 'A GUID identifying the claim for which a qualifier is being set',
				'property' => array(
					'Id of the snaks property.',
					'Should only be provided when creating a new qualifier or changing the property of an existing one'
				),
				'snaktype' => array(
					'The type of the snak.',
					'Should only be provided when creating a new qualifier or changing the type of an existing one'
				),
				'value' => array(
					'The new value of the qualifier. ',
					'Should only be provdied for PropertyValueSnak qualifiers'
				),
				'snakhash' => array(
					'The hash of the snak to modify.',
					'Should only be provided for existing qualifiers'
				),
			)
		);
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge(
			parent::getPossibleErrors(),
			$this->claimModificationHelper->getPossibleErrors(),
			array(
				array( 'code' => 'param-missing', 'info' => $this->msg( 'wikibase-api-param-missing' )->text() ),
			)
		);
	}

	/**
	 * @see ApiBase::getDescription
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getDescription() {
		return array(
			'API module for creating a qualifier or setting the value of an existing one.'
		);
	}

	/**
	 * @see ApiBase::getExamples
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetqualifier&claim=Q2$4554c0f4-47b2-1cd9-2db9-aa270064c9f3&property=P1&value=GdyjxP8I6XB3&snaktype=value&token=foobar' => 'Set the qualifier for the given claim with property P1 to string value GdyjxP8I6XB3',
		);
	}
}
