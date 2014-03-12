<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Lib\Serializers\ClaimSerializer;
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

	/**
	 * @var ClaimGuidValidator
	 */
	protected $claimGuidValidator;

	/**
	 * @var ClaimGuidParser
	 */
	protected $claimGuidParser;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $prefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $prefix );

		//TODO: provide a mechanism to override the services
		$this->claimGuidValidator = WikibaseRepo::getDefaultInstance()->getClaimGuidValidator();
		$this->claimGuidParser = WikibaseRepo::getDefaultInstance()->getClaimGuidParser();
	}

	/**
	 * @see \ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		list( $id, $claimGuid ) = $this->getIdentifiers( $params );

		$entityId = $this->idParser->parse( $id );
		$entityRevision = $entityId ? $this->loadEntityRevision( $entityId ) : null;
		$entity = $entityRevision->getEntity();

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
			$parsedProperty = $this->idParser->parse( $params['property'] );
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
		if ( $this->claimGuidValidator->validateFormat( $claimGuid ) === false ) {
			$this->dieUsage( 'Invalid claim guid' , 'invalid-guid' );
		}

		return $this->claimGuidParser->parse( $claimGuid )->getEntityId()->getSerialization();
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
