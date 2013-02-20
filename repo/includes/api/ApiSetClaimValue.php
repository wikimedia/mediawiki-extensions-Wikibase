<?php

namespace Wikibase;
use ApiBase, MWException;

/**
 * API module for setting the DataValue contained by the main snak of a claim.
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
 * @author Daniel Kinzler
 */
class ApiSetClaimValue extends ApiModifyClaim {

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

		$params = $this->extractRequestParams();
		$summary = $this->createSummary( $params );

		$claim = $this->updateClaim(
			$content->getEntity(),
			$params['claim'],
			$params['snaktype'],
			isset( $params['value'] ) ? \FormatJson::decode( $params['value'], true ) : null
		);

		$summary->addAutoCommentArgs( 1 ); // always just one. //XXX: put the property ID here?
		$summary->addAutoSummaryArgs( $claim->getPropertyId()->getPrefixedId() );
		//TODO: for PropertyValueSnak, we could try to get smart and show some rendering of the DataValue

		$this->saveChanges( $content, $summary );

		$this->outputClaim( $claim );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Updates the claim with specified GUID to have a main snak with provided value.
	 * The claim is modified in the passed along entity and is returned as well.
	 *
	 * @since 0.3
	 *
	 * @param Entity $entity
	 * @param string $guid
	 * @param string $snakType
	 * @param string|null $value
	 *
	 * @return Claim
	 */
	protected function updateClaim( Entity $entity, $guid, $snakType, $value = null ) {
		$claims = new Claims( $entity->getClaims() );

		if ( !$claims->hasClaimWithGuid( $guid ) ) {
			$this->dieUsage( 'No such claim', 'setclaimvalue-claim-not-found' );
		}

		$claim = $claims->getClaimWithGuid( $guid );

		$constructorArguments = array( $claim->getMainSnak()->getPropertyId() );

		if ( $value !== null ) {
			/**
			 * @var PropertyContent $content
			 */
			$content = EntityContentFactory::singleton()->getFromId( $claim->getMainSnak()->getPropertyId() );

			if ( $content === null ) {
				$this->dieUsage(
					'The value cannot be interpreted since the property cannot be found, and thus the type of the value not be determined',
					'setclaimvalue-property-not-found'
				);
			}

			$constructorArguments[] = $content->getProperty()->newDataValue( $value );
		}

		$claim->setMainSnak( SnakObject::newFromType( $snakType, $constructorArguments ) );

		$entity->setClaims( $claims );

		return $claim;
	}

	/**
	 * @see ApiBase::getAllowedParams
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
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
		) );
	}

	/**
	 * @see ApiBase::getParamDescription
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'claim' => 'A GUID identifying the claim',
			'snaktype' => 'The type of the snak',
			'value' => 'The value to set the datavalue of the the main snak of the claim to',
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
			'API module for setting the value of a Wikibase claim.'
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
}
