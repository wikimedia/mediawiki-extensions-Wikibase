<?php

namespace Wikibase\Api;

use ApiBase;
use DataValues\IllegalValueException;
use MWException;

use ValueParsers\ParseException;
use Wikibase\EntityContent;
use Wikibase\EntityId;
use Wikibase\Entity;
use Wikibase\EntityContentFactory;
use Wikibase\Claim;
use Wikibase\Property;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Snaks;
use Wikibase\SnakFactory;
use Wikibase\PropertyValueSnak;
use Wikibase\LibRegistry;
use Wikibase\Settings;
use Wikibase\Lib\ClaimGuidValidator;

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
 */
class SetQualifier extends ApiWikibase {

	// TODO: automcomment
	// TODO: example
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

		$this->checkParameterRequirements();

		$content = $this->getEntityContent();

		$claim = $this->doSetQualifier(
			$content->getEntity()
		);

		$this->saveChanges( $content );

		$this->outputClaim( $claim );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Checks if the required parameters are set and the ones that make no sense given the
	 * snaktype value are not set.
	 *
	 * @since 0.2
	 */
	protected function checkParameterRequirements() {
		$params = $this->extractRequestParams();

		if ( !isset( $params['snakhash'] ) ) {
			if ( !isset( $params['snaktype'] ) ) {
				$this->dieUsage(
					'When creating a new qualifier (ie when not providing a snakhash) a snaktype should be specified',
					'setqualifier-snaktype-required'
				);
			}

			if ( !isset( $params['property'] ) ) {
				$this->dieUsage(
					'When creating a new qualifier (ie when not providing a snakhash) a property should be specified',
					'setqualifier-property-required'
				);
			}
		}

		if ( isset( $params['snaktype'] ) && $params['snaktype'] === 'value' && !isset( $params['value'] ) ) {
			$this->dieUsage(
				'When setting a qualifier that is a PropertyValueSnak, the value needs to be provided',
				'setqualifier-value-required'
			);
		}
	}

	/**
	 * @since 0.3
	 *
	 * @return EntityContent
	 */
	protected function getEntityContent() {
		$params = $this->extractRequestParams();

		// @todo generalize handling of settings in api modules
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$entityPrefixes = $settings->getSetting( 'entityPrefixes' );
		$claimGuidValidator = new ClaimGuidValidator( $entityPrefixes );

		if ( !( $claimGuidValidator->validate( $params['claim'] ) ) ) {
			$this->dieUsage( 'Invalid claim guid', 'setqualifier-invalid-guid' );
		}

		$entityId = EntityId::newFromPrefixedId( Entity::getIdFromClaimGuid( $params['claim'] ) );
		$entityTitle = EntityContentFactory::singleton()->getTitleForId( $entityId );

		if ( $entityTitle === null ) {
			$this->dieUsage( 'No such entity', 'setqualifier-entity-not-found' );
		}

		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;

		return $this->loadEntityContent( $entityTitle, $baseRevisionId );
	}

	/**
	 * @since 0.3
	 *
	 * @param Entity $entity
	 *
	 * @return Claim
	 */
	protected function doSetQualifier( Entity $entity ) {
		$params = $this->extractRequestParams();

		$claimGuid = $params['claim'];

		$claims = new \Wikibase\Claims( $entity->getClaims() );

		if ( !$claims->hasClaimWithGuid( $claimGuid ) ) {
			$this->dieUsage( 'No such claim', 'setqualifier-claim-not-found' );
		}

		$claim = $claims->getClaimWithGuid( $claimGuid );

		$this->updateQualifiers( $claim->getQualifiers() );

		$entity->setClaims( $claims );

		return $claim;
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
		}
		else {
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
			$this->dieUsage( 'No such qualifier', 'setqualifier-qualifier-not-found' );
		}

		$params = $this->extractRequestParams();

		$snak = $qualifiers->getSnak( $snakHash );
		$qualifiers->removeSnakHash( $snakHash );

		$propertyId = isset( $params['property'] ) ? $params['property'] : $snak->getPropertyId();

		if ( is_string( $propertyId ) ) {
			$propertyId = $this->getParsedPropertyId( $propertyId, 'invalid-property-id' );
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

			return $qualifiers->addSnak( $newQualifier );
		} catch ( IllegalValueException $ex ) {
			$this->dieUsage( $ex->getMessage(), 'setclaim-invalid-snak' );
		}

		return false; // we should never get here.
	}

	protected function getParsedPropertyId( $prefixedId, $errorCode ) {
		$entityIdParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();

		try {
			$entityId = $entityIdParser->parse( $prefixedId );

			if ( $entityId->getEntityType() !== Property::ENTITY_TYPE ) {
				$this->dieUsage( 'Not a property: ' . $prefixedId, $errorCode );
				return null;
			}
		}
		catch ( ParseException $parseException ) {
			$this->dieUsage( $parseException->getMessage(), $errorCode );
			return null;
		}

		return $entityId;
	}

	/**
	 * @param EntityId $propertyId
	 * @param string $snakType
	 * @param mixed $valueData
	 */
	protected function newSnak( EntityId $propertyId, $snakType, $valueData ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			$this->dieUsage( "Property expected, got " . $propertyId->getEntityType(), 'claim-invalid-snak' );
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
		$propertyId = $this->getParsedPropertyId( $params['property'], 'invalid-property-id' );

		try {
			$newQualifier = $this->newSnak(
				$propertyId,
				$params['snaktype'],
				isset( $params['value'] ) ? \FormatJson::decode( $params['value'], true ) : null
			);

			return $qualifiers->addSnak( $newQualifier );
		} catch ( IllegalValueException $ex ) {
			$this->dieUsage( $ex->getMessage(), 'setclaim-invalid-snak' );
		}

		return false; // we should never get here.
	}

	/**
	 * @since 0.3
	 *
	 * @param EntityContent $content
	 */
	protected function saveChanges( EntityContent $content ) {
		// collect information and create an EditEntity
		$summary = '/* wbsetqualifier */'; // TODO: automcomment
		$status = $this->attemptSaveEntity( $content,
			$summary,
			EDIT_UPDATE );

		$this->addRevisionIdFromStatusToResult( 'pageinfo', 'lastrevid', $status );
	}

	/**
	 * @since 0.3
	 *
	 * @param Claim $claim
	 */
	protected function outputClaim( Claim $claim ) {
		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $claim );
		$serializer->getOptions()->setIndexTags( $this->getResult()->getIsRawMode() );

		$this->getResult()->addValue(
			null,
			'claim',
			$serializer->getSerialized( $claim )
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
		return array(
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
			'bot' => null,
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
			// TODO
			// 'ex' => 'desc'
		);
	}

	/**
	 * @see \ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return true;
	}

}
