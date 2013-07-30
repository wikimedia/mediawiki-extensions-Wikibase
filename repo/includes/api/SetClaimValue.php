<?php

namespace Wikibase\Api;

use ApiBase, MWException;
use ApiMain;
use Wikibase\EntityId;
use Wikibase\Entity;
use Wikibase\Claims;
use Wikibase\ChangeOpClaim;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Repo\WikibaseRepo;

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
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetClaimValue extends ModifyClaim {

	/**
	 * see ApiBase::__construct()
	 *
	 * @param ApiMain $mainModule
	 * @param string  $moduleName
	 * @param string  $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );
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

		$entityId = EntityId::newFromPrefixedId( Entity::getIdFromClaimGuid( $params['claim'] ) );
		$entityContent = $this->getEntityContent( $entityId );
		$entity = $entityContent->getEntity();
		$claims = new Claims( $entity->getClaims() );
		$claimGuid = $params['claim'];
		$claim = $claims->getClaimWithGuid( $claimGuid );

		if ( $claim === null ) {
			$this->dieUsage( "No claim for GUID: $claimGuid" , 'no-such-claim' );
		}

		$snak = $this->getSnakInstance( $params, $claim->getMainSnak()->getPropertyId() );

		$summary = $this->createSummary( $params );
		$changeOp = new ChangeOpClaim( $claimGuid, $snak, WikibaseRepo::getDefaultInstance()->getIdFormatter() );
		$changeOp->apply( $entity, $summary );

		$this->saveChanges( $entityContent, $summary );

		$this->outputClaim( $claim );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		// @todo generalize handling of settings in api modules
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$entityPrefixes = $settings->getSetting( 'entityPrefixes' );
		$claimGuidValidator = new ClaimGuidValidator( $entityPrefixes );

		if ( !( $claimGuidValidator->validate( $params['claim'] ) ) ) {
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
			parent::getAllowedParams(),
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
				'token' => null,
				'baserevid' => array(
					ApiBase::PARAM_TYPE => 'integer',
				),
				'bot' => false,
			)
		);
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'no-such-claim', 'info' => $this->msg( 'wikibase-api-no-such-claim' )->text() ),
		) );
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
				'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
				'baserevid' => array( 'The numeric identifier for the revision to base the modification on.',
					"This is used for detecting conflicts during save."
				),
				'bot' => array( 'Mark this edit as bot',
					'This URL flag will only be respected if the user belongs to the group "bot".'
				),
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
			'api.php?action=wbsetclaimvalue&claim=q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F&snaktype=value&value={"entity-type":"item","numeric-id":1}&token=foobar&baserevid=7201010' => 'Sets the claim with the GUID of q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F to a value of q1',
		);
	}
}
