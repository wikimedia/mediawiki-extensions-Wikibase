<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use MWException;

use Wikibase\EntityContent;
use Wikibase\Entity;
use Wikibase\EntityContentFactory;
use Wikibase\Claim;
use Wikibase\Settings;
use Wikibase\Autocomment;

/**
 * API module for removing qualifiers from a claim.
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
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class RemoveQualifiers extends \Wikibase\ApiModifyClaim {

	// TODO: automcomment
	// TODO: example
	// TODO: rights
	// TODO: conflict detection
	// TODO: claim uniqueness

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$params = $this->extractRequestParams();
		$content = $this->getEntityContentForClaim( $params['claim'] );

		$this->doRemoveQualifiers( $content->getEntity() );

		$this->saveChanges( $content );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @since 0.3
	 *
	 * @param Entity $entity
	 */
	protected function doRemoveQualifiers( Entity $entity ) {
		$params = $this->extractRequestParams();

		$claim = $this->getClaim( $entity, $params['claim'] );

		$qualifiers = $claim->getQualifiers();

		foreach ( array_unique( $params['qualifiers'] ) as $qualifierHash ) {
			if ( !$qualifiers->hasSnakHash( $qualifierHash ) ) {
				// TODO: does $qualifierHash need to be escaped?
				$this->dieUsage( 'There is no qualifier with hash ' . $qualifierHash, 'removequalifiers-qualifier-not-found' );
			}

			$qualifiers->removeSnakHash( $qualifierHash );
		}
	}

	/**
	 * @since 0.3
	 *
	 * @param Entity $entity
	 * @param string $claimGuid
	 *
	 * @return Claim
	 */
	protected function getClaim( Entity $entity, $claimGuid ) {
		$claims = new \Wikibase\Claims( $entity->getClaims() );

		if ( !$claims->hasClaimWithGuid( $claimGuid ) ) {
			$this->dieUsage( 'No such claim', 'removequalifiers-claim-not-found' );
		}

		$claim = $claims->getClaimWithGuid( $claimGuid );

		assert( $claim instanceof Claim );

		return $claim;
	}

	/**
	 * @see  ApiAutocomment::getTextForComment()
	 */
	public function getTextForComment( array $params, $plural = 1 ) {
		return Autocomment::formatAutoComment(
			$this->getModuleName(),
			array( count( $params['qualifiers'] ) )
		);
	}

	/**
	 * @see  ApiAutocomment::getTextForSummary()
	 */
	public function getTextForSummary( array $params ) {
		return Autocomment::formatAutoSummary(
			Autocomment::pickValuesFromParams( $params, 'qualifiers' )
		);
	}

	/**
	 * @see ApiBase::getAllowedParams
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'claim' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'qualifiers' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_ISMULTI => true,
			),
		) );
	}

	/**
	 * @see ApiBase::getParamDescription
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'claim' => 'A GUID identifying the claim from which to remove qualifiers',
			'qualifiers' => 'Snak hashes of the querliers to remove',
		) );
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
			'API module for removing a qualifier from a claim.'
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
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbremovequalifiers';
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

	/**
	 * @see \ApiBase::needsToken()
	 */
	public function needsToken() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens' ) : true;
	}

	/**
	 * @see \ApiBase::mustBePosted()
	 */
	public function mustBePosted() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithPost' ) : true;
	}

	/**
	 * @see \ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return true;
	}

}
