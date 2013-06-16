<?php

namespace Wikibase\Api;

use ApiBase, MWException;

use DataValues\IllegalValueException;
use InvalidArgumentException;
use UsageException;
use ValueParsers\ParseException;
use Wikibase\EntityId;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SnakFactory;
use Wikibase\LibRegistry;
use Wikibase\Claim;
use Wikibase\Autocomment;
use Wikibase\Settings;
use Wikibase\Summary;
use Wikibase\Snak;

/**
 * API module for creating claims.
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
 * @since 0.2
 *
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class CreateClaim extends ModifyClaim {

	// TODO: rights

	/**
	 * @see \ApiBase::execute
	 *
	 * @since 0.2
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );

		$this->checkParameterRequirements();

		$entityContent = $this->getEntityContent();

		// It is possible we get an exception from this method because of specifying
		// a non existing-property, specifying an entity id for an entity with wrong
		// entity type or providing an invalid DataValue.
		try {
			$snak = $this->getSnakInstance();
		}
		catch ( IllegalValueException $ex ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( $ex->getMessage(), 'claim-invalid-snak' );
		}
		catch ( InvalidArgumentException $ex ) {
			// shouldn't happen, but might.
			wfProfileOut( __METHOD__ );
			$this->dieUsage( $ex->getMessage(), 'claim-invalid-snak' );
		}
		catch ( ParseException $parseException ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( $parseException->getMessage(), 'claim-invalid-guid' );
		}

		$claim = $this->addClaim( $entityContent->getEntity(), $snak );
		$summary = $this->createSummary( $snak, 'create' );

		$this->saveChanges( $entityContent, $summary );

		$this->outputClaim( $claim );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Constructs a new Claim based on the arguments provided to the API,
	 * adds it to the Entity and saves it.
	 *
	 * On success, the added Claim is returned as part of the Status object.
	 *
	 * @since 0.2
	 *
	 * @param \Wikibase\Entity $entity
	 * @param \Wikibase\Snak $snak
	 *
	 * @return Claim
	 */
	protected function addClaim( Entity $entity, Snak $snak ) {
		wfProfileIn( __METHOD__ );
		$claim = $entity->newClaim( $snak );

		$entity->addClaim( $claim );

		wfProfileOut( __METHOD__ );
		return $claim;
	}

	/**
	 * @since 0.3
	 *
	 * @param \Wikibase\EntityContent $content
	 * @param \Wikibase\Summary $summary
	 */
	protected function saveChanges( EntityContent $content, Summary $summary ) {
		$status = $this->attemptSaveEntity(
			$content,
			$summary->toString(),
			EDIT_UPDATE
		);

		$this->addRevisionIdFromStatusToResult( 'pageinfo', 'lastrevid', $status );
	}

	/**
	 * Checks if the required parameters are set and the ones that make no sense given the
	 * snaktype value are not set.
	 *
	 * @since 0.2
	 */
	protected function checkParameterRequirements() {
		$params = $this->extractRequestParams();

		if ( $params['snaktype'] == 'value' XOR isset( $params['value'] ) ) {
			if ( $params['snaktype'] == 'value' ) {
				$this->dieUsage( 'A value needs to be provided when creating a claim with PropertyValueSnak snak', 'claim-value-missing' );
			}
			else {
				$this->dieUsage( 'You cannot provide a value when creating a claim with no PropertyValueSnak as main snak', 'claim-value-set' );
			}
		}

		if ( !isset( $params['property'] ) ) {
			$this->dieUsage( 'A property ID needs to be provided when creating a claim with a Snak', 'claim-property-id-missing' );
		}
	}

	/**
	 * @since 0.2
	 *
	 * @return EntityContent
	 */
	protected function getEntityContent() {
		$params = $this->extractRequestParams();

		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;

		$entityId = EntityId::newFromPrefixedId( $params['entity'] );
		$entityTitle = $entityId ? EntityContentFactory::singleton()->getTitleForId( $entityId ) : null;
		$entityContent = $entityTitle === null ? null : $this->loadEntityContent( $entityTitle, $baseRevisionId );

		if ( $entityContent === null ) {
			$this->dieUsage( 'Entity not found, snak not created', 'entity-not-found' );
		}

		return $entityContent;
	}

	/**
	 * @since 0.2
	 *
	 * @return Snak
	 * @throws ParseException
	 * @throws IllegalValueException
	 */
	protected function getSnakInstance() {
		$params = $this->extractRequestParams();

		$snakType = $params['snaktype'];
		$valueData = isset( $params['value'] ) ? \FormatJson::decode( $params['value'], true ) : null;

		//TODO: Inject all this, or at least initialize it in a central location.
		$factory = new SnakFactory();
		$entityIdParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
		$dataTypeLookup = WikibaseRepo::getDefaultInstance()->getPropertyDataTypeLookup();
		$dataTypeFactory = WikibaseRepo::getDefaultInstance()->getDataTypeFactory();

		$entityId = $entityIdParser->parse( $params['property'] );
		//TODO: make sure $entityId refers to a property

		//XXX: The below is somewhat cumbersome. Can we move this into a service?
		$dataTypeId = $valueData === null ? null : $dataTypeLookup->getDataTypeIdForProperty( $entityId );
		$dataType = $dataTypeId === null ? null : $dataTypeFactory->getType( $dataTypeId );
		$valueType = $dataType === null ? null : $dataType->getDataValueType();

		return $factory->newSnak(
			$entityId,
			$snakType,
			$valueType,
			$valueData
		);
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
	 * @see \ApiBase::getAllowedParams
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'entity' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'snaktype' => array(
				ApiBase::PARAM_TYPE => array( 'value', 'novalue', 'somevalue' ),
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
			'token' => null,
			'baserevid' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'bot' => null,
		);
	}

	/**
	 * @see \ApiBase::getParamDescription
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'entity' => 'Id of the entity you are adding the claim to',
			'property' => 'Id of the snaks property',
			'value' => 'Value of the snak when creating a claim with a snak that has a value',
			'snaktype' => 'The type of the snak',
			'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
			'baserevid' => array( 'The numeric identifier for the revision to base the modification on.',
				"This is used for detecting conflicts during save."
			),
			'bot' => array( 'Mark this edit as bot',
				'This URL flag will only be respected if the user belongs to the group "bot".'
			),
		);
	}

	/**
	 * @see \ApiBase::getDescription
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getDescription() {
		return array(
			'API module for creating Wikibase claims.'
		);
	}

	/**
	 * @see \ApiBase::getExamples
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbcreateclaim&entity=q42&property=p9001&snaktype=novalue&token=foobar&baserevid=7201010',
			'api.php?action=wbcreateclaim&entity=q42&property=p9001&snaktype=value&value={"entity-type":"item","numeric-id":1}&token=foobar&baserevid=7201010',
			// 'ex' => 'desc'
		);
	}
}
