<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Store\EntityRevisionLookup;
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
	private $claimGuidValidator;

	/**
	 * @var ClaimGuidParser
	 */
	private $claimGuidParser;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

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
		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		list( $id, $claimGuid ) = $this->getIdentifiers( $params );

		try {
			$entityId = $this->getIdParser()->parse( $id );
		} catch ( EntityIdParsingException $e ) {
			$this->dieException( $e, 'param-invalid' );
		}

		$entityRevision = $entityId ? $this->loadEntityRevision( $entityId, EntityRevisionLookup::LATEST_FROM_SLAVE ) : null;
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
	}

	private function validateParameters( array $params ) {
		if ( !isset( $params['entity'] ) && !isset( $params['claim'] ) ) {
			$this->dieError( 'Either the entity parameter or the claim parameter need to be set', 'param-missing' );
		}
	}

	/**
	 * @since 0.3
	 *
	 * @param Entity $entity
	 * @param null|string $claimGuid
	 *
	 * @return Claim[]
	 */
	private function getClaims( Entity $entity, $claimGuid ) {
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

	private function claimMatchesFilters( Claim $claim ) {
		return $this->rankMatchesFilter( $claim->getRank() )
			&& $this->propertyMatchesFilter( $claim->getPropertyId() );
	}

	private function rankMatchesFilter( $rank ) {
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

	private function propertyMatchesFilter( EntityId $propertyId ) {
		$params = $this->extractRequestParams();

		if ( isset( $params['property'] ) ){
			try {
				$parsedProperty = $this->getIdParser()->parse( $params['property'] );
			} catch ( EntityIdParsingException $e ) {
				$this->dieException( $e, 'param-invalid' );
			}

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
	private function getIdentifiers( $params ) {
		if ( isset( $params['claim'] ) ) {
			$claimGuid = $params['claim'];
			$entityId = $this->getEntityIdFromClaimGuid( $params['claim'] );

			if( isset( $params['entity'] ) && $entityId !== $params['entity'] ) {
				$this->dieError( 'If both entity id and claim key are provided they need to point to the same entity', 'param-illegal' );
			}
		} else {
			$claimGuid = null;
			$entityId = $params['entity'];
		}

		return array( $entityId, $claimGuid );
	}

	private function getEntityIdFromClaimGuid( $claimGuid ) {
		if ( $this->claimGuidValidator->validateFormat( $claimGuid ) === false ) {
			$this->dieError( 'Invalid claim guid' , 'invalid-guid' );
		}

		return $this->claimGuidParser->parse( $claimGuid )->getEntityId()->getSerialization();
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
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
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			"action=wbgetclaims&entity=Q42" =>
				"apihelp-wbgetclaims-example-1",
			"action=wbgetclaims&entity=Q42&property=P2" =>
				"apihelp-wbgetclaims-example-2",
			"action=wbgetclaims&entity=Q42&rank=normal" =>
				"apihelp-wbgetclaims-example-3",
			'action=wbgetclaims&claim=Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F' =>
				'apihelp-wbgetclaims-example-4',
		);
	}

}
