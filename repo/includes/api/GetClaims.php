<?php

namespace Wikibase\Api;

use ApiBase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Entity;
use Wikibase\Claims;
use Wikibase\Claim;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for getting claims.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Adam Shorland
 */
class GetClaims extends ApiWikibase {

	// TODO: conflict detection

	/**
	 * @see \ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		//@todo validate

		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		list( $id, $claimGuid ) = $this->getIdentifiers( $params );

		$entityIdParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
		$entityId = $entityIdParser->parse( $id );
		$entity = $entityId ? $this->getEntity( $entityId ) : null;

		if ( !$entity ) {
			$this->dieUsage( "No entity found matching ID $id", 'no-such-entity' );
		}

		if( $params['ungroupedlist'] ) {
			$this->getResultBuilder()->getOptions()
				->setOption(
					SerializationOptions::OPT_GROUP_BY_PROPERTIES,
					array()
				);
		}

		$claims = $this->getClaims( $entity, $claimGuid );
		$this->getResultBuilder()->addClaims( $claims, null );

		wfProfileOut( __METHOD__ );
	}

	protected function validateParameters( array $params ) {
		if ( !isset( $params['entity'] ) && !isset( $params['claim'] ) ) {
			$this->dieUsage( 'Either the entity parameter or the claim parameter need to be set', 'param-missing' );
		}
	}

	/**
	 * @see \ApiBase::getPossibleErrors()
	 * @return array
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'no-such-entity', 'info' => $this->msg( 'wikibase-api-no-such-entity' )->text()  ),
			array( 'code' => 'param-missing', 'info' => $this->msg( 'wikibase-api-param-missing' )->text() ),
			array( 'code' => 'param-illegal', 'info' => $this->msg( 'wikibase-api-param-illegal' )->text() ),
		) );
	}

	/**
	 * @since 0.3
	 *
	 * @param EntityId $id
	 *
	 * @return Entity
	 */
	protected function getEntity( EntityId $id ) {
		$content = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getFromId( $id );

		if ( $content === null ) {
			$this->dieUsage( 'The specified entity does not exist, so it\'s claims cannot be obtained', 'no-such-entity' );
		}

		return $content->getEntity();
	}

	/**
	 * @since 0.3
	 *
	 * @param Entity $entity
	 * @param null|string $claimGuid
	 *
	 * @return Claim[]
	 */
	protected function getClaims( Entity $entity, $claimGuid ) {
		$claimsList = new Claims( $entity->getClaims() );

		if ( $claimGuid !== null ) {
			return $claimsList->hasClaimWithGuid( $claimGuid ) ?
				array( $claimsList->getClaimWithGuid( $claimGuid ) ) : array();
		}

		$claims = array();

		/** @var Claim $claim */
		foreach ( $claimsList as $claim ) {
			if ( $this->claimMatchesFilters( $claim ) ) {
				$claims[] = $claim;
			}
		}

		return $claims;
	}

	protected function claimMatchesFilters( Claim $claim ) {
		return $this->rankMatchesFilter( $claim->getRank() )
			&& $this->propertyMatchesFilter( $claim->getPropertyId() );
	}

	protected function rankMatchesFilter( $rank ) {
		if ( $rank === null ) {
			return true;
		}
		$params = $this->extractRequestParams();

		if( isset( $params['rank'] ) ){
			$unserializedRank = ClaimSerializer::unserializeRank( $params['rank'] );
			$matchFilter = $rank === $unserializedRank;
			return $matchFilter;
		}

		return true;
	}

	protected function propertyMatchesFilter( EntityId $propertyId ) {
		$params = $this->extractRequestParams();

		if ( isset( $params['property'] ) ){
			$parsedProperty = WikibaseRepo::getDefaultInstance()->getEntityIdParser()->parse( $params['property'] );
			$matchFilter = $propertyId->equals( $parsedProperty );
			return $matchFilter;
		}

		return true;
	}

	/**
	 * Obtains the id of the entity for which to obtain claims and the claim GUID
	 * in case it was also provided.
	 *
	 * @since 0.3
	 *
	 * @param $params
	 * @return array
	 * First element is a prefixed entity id
	 * Second element is either null or a claim GUID
	 */
	protected function getIdentifiers( $params ) {
		if ( isset( $params['claim'] ) ) {
			$claimGuid = $params['claim'];
			$entityId = $this->getEntityIdFromClaimGuid( $params['claim'] );

			if( isset( $params['entity'] ) && $entityId !== $params['entity'] ) {
				$this->dieUsage( 'If both entity id and claim key are provided they need to point to the same entity', 'param-illegal' );
			}
		} else {
			$claimGuid = null;
			$entityId = $params['entity'];
		}

		return array( $entityId, $claimGuid );
	}

	protected function getEntityIdFromClaimGuid( $claimGuid ) {
		$claimGuidValidator = WikibaseRepo::getDefaultInstance()->getClaimGuidValidator(); //TODO: inject.

		if ( $claimGuidValidator->validateFormat( $claimGuid ) === false ) {
			$this->dieUsage( 'Invalid claim guid' , 'invalid-guid' );
		}

		$claimGuidParser = WikibaseRepo::getDefaultInstance()->getClaimGuidParser();

		return $claimGuidParser->parse( $claimGuid )->getEntityId()->getSerialization();
	}

	/**
	 * @see ApiBase::getAllowedParams
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'entity' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'property' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'claim' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'rank' => array(
				ApiBase::PARAM_TYPE => ClaimSerializer::getRanks(),
			),
			'props' => array(
				ApiBase::PARAM_TYPE => array(
					'references',
				),
				ApiBase::PARAM_DFLT => 'references',
			),
			'ungroupedlist' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false,
			),
		);
	}

	/**
	 * @see \ApiBase::getParamDescription
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'entity' => 'Id of the entity from which to obtain claims. Required unless claim GUID is provided.',
			'property' => 'Optional filter to only return claims with a main snak that has the specified property.',
			'claim' => 'A GUID identifying the claim. Required unless entity is provided. The GUID is the globally unique identifier for a claim, e.g. "q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F".',
			'rank' => 'Optional filter to return only the claims that have the specified rank',
			'props' => 'Some parts of the claim are returned optionally. This parameter controls which ones are returned.',
			'ungroupedlist' => 'Do not group snaks by property id',
		);
	}

	/**
	 * @see \ApiBase::getDescription
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getDescription() {
		return array(
			'API module for getting Wikibase claims.'
		);
	}

	/**
	 * @see \ApiBase::getExamples
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			"api.php?action=wbgetclaims&entity=Q42" =>
				"Get claims for item with ID Q42",
			"api.php?action=wbgetclaims&entity=Q42&property=P2" =>
				"Get claims for item with ID Q42 and property with ID P2",
			"api.php?action=wbgetclaims&entity=Q42&rank=normal" =>
				"Get claims for item with ID Q42 that are ranked as normal",
			'api.php?action=wbgetclaims&claim=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F' =>
				'Get claim with GUID of Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F',
		);
	}

}
