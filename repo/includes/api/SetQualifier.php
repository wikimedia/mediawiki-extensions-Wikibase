<?php

namespace Wikibase\Api;

use ApiBase;
use DataValues\IllegalValueException;
use ApiMain;
use MWException;
use ValueParsers\ParseException;
use Wikibase\EntityContent;
use Wikibase\EntityId;
use Wikibase\Entity;
use Wikibase\EntityContentFactory;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\Lib\PropertyNotFoundException;
use Wikibase\Property;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Snak;
use Wikibase\Snaks;
use Wikibase\SnakFactory;
use Wikibase\PropertyValueSnak;
use Wikibase\LibRegistry;
use Wikibase\Settings;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;
use Wikibase\Validators\SnakValidator;
use Wikibase\ChangeOpQualifier;

/**
 * API module for creating a qualifier or setting the value of an existing one.
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
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetQualifier extends ModifyClaim {

	// TODO: rights
	// TODO: conflict detection
	// TODO: more explicit support for snak merging?
	// TODO: claim uniqueness

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
		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$entityTitle = $this->claimModificationHelper->getEntityTitle( $entityId );
		// TODO: put loadEntityContent into a separate helper class for great reuse!
		$entityContent = $this->loadEntityContent( $entityTitle, $baseRevisionId );
		$entity = $entityContent->getEntity();
		$summary = $this->claimModificationHelper->createSummary( $params, $this );

		$claimGuid = $params['claim'];
		$claims = new Claims( $entity->getClaims() );

		if ( !$claims->hasClaimWithGuid( $claimGuid ) ) {
			$this->dieUsage( 'Could not find the claim' , 'no-such-claim' );
		}

		$claim = $claims->getClaimWithGuid( $claimGuid );

		$changeOp = $this->getChangeOp();
		$changeOp->apply( $entity, $summary );

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
	 * @since 0.3
	 *
	 * @param Snaks $qualifiers
	 */
	protected function updateQualifiers( Snaks $qualifiers ) {
		$params = $this->extractRequestParams();

		if ( isset( $params['snakhash'] ) ) {
			$this->updateQualifier( $qualifiers, $params['snakhash'] );
		} else {
			$this->addQualifier( $qualifiers );
		}
	}

	/**
	 * @since 0.3
	 *
	 * @param Snaks $qualifiers
	 * @param string $snakHash
	 *
	 * @return boolean If the snak was added or not
	 * This is false if there already is a snak with the new value in the list.
	 * In such cases the snak will thus have been merged/removed.
	 */
	protected function updateQualifier( Snaks $qualifiers, $snakHash ) {
		if ( !$qualifiers->hasSnakHash( $snakHash ) ) {
			$this->dieUsage( 'Could not find the qualifier' , 'no-such-qualifier' );
		}

		$params = $this->extractRequestParams();

		$snak = $qualifiers->getSnak( $snakHash );
		$qualifiers->removeSnakHash( $snakHash );

		$propertyId = isset( $params['property'] ) ? $params['property'] : $snak->getPropertyId();

		if ( is_string( $propertyId ) ) {
			$propertyId = $this->claimModificationHelper->getEntityIdFromString( $propertyId );
		}

		$snakType = isset( $params['snaktype'] ) ? $params['snaktype'] : $snak->getType();

		if ( isset( $params['value'] ) ) {
			$snakValue = \FormatJson::decode( $params['value'] );
		}
		elseif ( $snak instanceof PropertyValueSnak ) {
			$snakValue = $snak->getDataValue()->getArrayValue();
		}
		else {
			$snakValue = null;
		}

		try {
			$newQualifier = $this->newSnak( $propertyId, $snakType, $snakValue );
			$this->snakValidation->validateSnak( $newQualifier );

			return $qualifiers->addSnak( $newQualifier );
		} catch ( IllegalValueException $illegalValueException ) {
			//Note: This handles failures during snak instantiation, not validation.
			//      Validation errors are handled by the validation helper.
			$this->dieUsage( $illegalValueException->getMessage(), 'invalid-snak' );
		} catch ( PropertyNotFoundException $ex ) {
			$this->dieUsage( $ex->getMessage(), 'invalid-snak' );
		}

		return false; // we should never get here.
	}

	/**
	 * @param EntityId $propertyId
	 * @param string $snakType
	 * @param mixed $valueData
	 */
	protected function newSnak( EntityId $propertyId, $snakType, $valueData ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			$this->dieUsage( "Property expected, got " . $propertyId->getEntityType(), 'invalid-snak' );
		}

		//TODO: Inject this, or at least initialize it in a central location.
		$factory = WikibaseRepo::getDefaultInstance()->getSnakConstructionService();

		return $factory->newSnak(
			$propertyId,
			$snakType,
			$valueData
		);
	}

	/**
	 * @since 0.3
	 *
	 * @param Snaks $qualifiers
	 *
	 * @return boolean If the snak was added or not
	 * This is false if there already is a snak with the new value in the list.
	 * In such cases the snak will thus have been merged/removed.
	 */
	protected function addQualifier( Snaks $qualifiers ) {
		$params = $this->extractRequestParams();
		$propertyId = $this->claimModificationHelper->getEntityIdFromString( $params['property'] );

		$newQualifier = $this->claimModificationHelper->getSnakInstance( $params, $propertyId );

		return $qualifiers->addSnak( $newQualifier );
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
				'token' => null,
				'baserevid' => array(
					ApiBase::PARAM_TYPE => 'integer',
				),
				'bot' => false,
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
				'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
				'baserevid' => array(
					'The numeric identifier for the revision to base the modification on.',
					"This is used for detecting conflicts during save."
				),
				'bot' => array( 'Mark this edit as bot',
					'This URL flag will only be respected if the user belongs to the group "bot".'
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
				array( 'code' => 'no-such-claim', 'info' => $this->msg( 'wikibase-api-no-such-claim' )->text() ),
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
			'api.php?action=wbsetqualifier&claim=q2$4554c0f4-47b2-1cd9-2db9-aa270064c9f3&property=q1&value=GdyjxP8I6XB3&snaktype=value&token=foobar' => 'Set the qualifier for the given claim to string value GdyjxP8I6XB3',
		);
	}
}
