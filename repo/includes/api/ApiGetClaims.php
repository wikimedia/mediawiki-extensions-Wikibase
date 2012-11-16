<?php

namespace Wikibase;
use ApiBase, MWException;

/**
 * API module for getting claims.
 *
 * TODO: high level tests
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.3
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiGetClaims extends Api {

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( "Wikibase-" . __METHOD__ );

		list( $entityId, $claimGuid ) = $this->getIdentifiers();

		$entity = $this->getEntity( EntityId::newFromPrefixedId( $entityId ) );

		$this->outputClaims( $this->getClaims( $entity, $claimGuid ) );

		wfProfileOut( "Wikibase-" . __METHOD__ );
	}

	/**
	 * @since 0.3
	 *
	 * @param Claim[] $claims
	 */
	protected function outputClaims( array $claims ) {
		// TODO: hold into account props parameter
		$serializer = new ClaimsSerializer( $this->getResult() );
		$serializedClaims = $serializer->getSerialized( new ClaimList( $claims ) );

		$this->getResult()->addValue(
			null,
			'claims',
			$serializedClaims
		);
	}

	/**
	 * @since 0.3
	 *
	 * @param EntityId $id
	 *
	 * @return Entity
	 */
	protected function getEntity( EntityId $id ) {
		$content = EntityContentFactory::singleton()->getFromId( $id );

		if ( $content === null ) {
			$this->dieUsage( "The specified entity does not exist, so it's claims cannot be obtained", 'getclaims-entity-not-found' );
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
		if ( $claimGuid !== null ) {
			return $entity->hasClaimWithGuid( $claimGuid ) ?
				$entity->getClaimWithGuid( $claimGuid ) : array();
		}

		$claims = array();
		$params = $this->extractRequestParams();

		// TODO: we probably need this elswhere, so make filter methods in Claim
		$rank = isset( $params['rank'] ) ? ClaimSerializer::unserializeRank( $params['rank'] ) : false;
		$propertyId = isset( $params['property'] ) ? $params['property'] : false;

		/**
		 * @var Claim $claim
		 */
		foreach ( $entity->getClaims() as $claim ) {
			$rankIsOk = $rank === false
				|| ( $claim instanceof Statement && $claim->getRank() === $rank );

			if ( $rankIsOk
				&& ( $propertyId === false || $propertyId === $claim->getPropertyId() ) ) {
				$claims[] = $claim;
			}
		}

		return $claims;
	}

	/**
	 * Obtains the id of the entity for which to obtain claims and the claim GUID
	 * in case it was also provided.
	 *
	 * @since 0.3
	 *
	 * @return array
	 * First element is a prefixed entity id
	 * Second element is either null or a claim GUID
	 */
	protected function getIdentifiers() {
		$params = $this->extractRequestParams();

		if ( !isset( $params['entity'] ) && !isset( $params['key'] ) ) {
			$this->dieUsage( 'Either the entity parameter or the key parameter need to be set', 'getclaims-entity-or-key' );
		}

		$claimGuid = null;

		if ( isset( $params['entity'] ) && isset( $params['key'] ) ) {
			list( $entityId, $claimGuid ) = $this->parseClaimKey( $params['key'] );

			if ( $entityId !== $params['entity'] ) {
				$this->dieUsage( 'If both entity id and claim key are provided they need to point to the same entity', 'getclaims-id-mismatch' );
			}
		}
		else if ( isset( $params['entity'] ) ) {
			$entityId = $params['entity'];
		}
		else {
			list( $entityId, $claimGuid ) = $this->parseClaimKey( $params['key'] );
		}

		return array( $entityId, $claimGuid );
	}

	/**
	 * Parses a claim key to prefixed entity id and claim GUID.
	 * Note: might be moved out of API.
	 *
	 * @since 0.3
	 *
	 * @param string $claimKey
	 *
	 * @return array
	 * First element is a prefixed entity id
	 * Second element is either null or a claim GUID
	 * @throws MWException
	 */
	protected function parseClaimKey( $claimKey ) {
		$keyParts = explode( '#', $claimKey );

		if ( count( $keyParts ) !== 2 ) {
			throw new MWException( 'A claim key should have a single # in it' );
		}

		return $keyParts;
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
			'key' => array(
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
			'token' => null,
			'baserevid' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
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
		return array(
			'entity' => 'Id of the entity from which to obtain claims. Required unless key is provided.',
			'property' => 'Optional filter to only return claims with a main snak that has the specified property.',
			'key' => 'A GUID identifying the claim. Required unless entity is provided.',
			'rank' => 'Optional filter to return only the claims that have the specified rank',
			'props' => 'Some parts of the claim are returned optionally. This parameter controls which ones are returned.',
			'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
			'baserevid' => array( 'The numeric identifier for the revision to base the modification on.',
				"This is used for detecting conflicts during save."
			),
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
			'API module for getting Wikibase claims.'
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
			// TODO
			// 'ex' => 'desc'
		);
	}

	/**
	 * @see ApiBase::getHelpUrls
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbgetclaims';
	}

	/**
	 * @see ApiBase::getVersion
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . '-' . WB_VERSION;
	}

}
