<?php

namespace Wikibase;
use ApiBase, MWException;

/**
 * API module for creating statements.
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
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiCreateClaim extends ApiBase implements ApiAutocomment {

	/**
	 * @see  ApiAutocomment::getTextForComment()
	 */
	public function getTextForComment( array $params, $plural = 1 ) {
		return Autocomment::formatAutoComment(
			'wbcreateclaim-' . $params['snaktype'],
			array(
				/*plural */ (int)isset( $params['value'] ) + (int)isset( $params['property'] )
			)
		);
	}

	/**
	 * @see  ApiAutocomment::getTextForSummary()
	 */
	public function getTextForSummary( array $params ) {
		return Autocomment::formatAutoSummary(
			Autocomment::pickValuesFromParams( $params, 'property', 'value' )
		);
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.2
	 */
	public function execute() {
		$this->checkParameterRequirements();

		$claim = $this->addClaim();

		$serializer = new ClaimSerializer( $this->getResult() );
		$serializedClaim = $serializer->getSerialized( $claim );

		$this->getResult()->addValue(
			null,
			'claim',
			$serializedClaim
		);
	}

	/**
	 * Constructs a new Claim based on the arguments provided to the API,
	 * adds it to the Entity and saves it.
	 *
	 * On success, the added Claim is returned. Else null is returned.
	 *
	 * @since 0.2
	 *
	 * @return Claim|null
	 * @throws MWException
	 */
	protected function addClaim() {
		$entityContent = $this->getEntityContent();
		if ( $entityContent === null ) {
			$this->dieUsage( 'Entity not found, snak not created', 'entity-not-found' );
		}

		$entity = $entityContent->getEntity();

		$snak = $this->getSnakInstance();

		$isStatement = false;

		if ( $entity instanceof StatementListAccess ) {
			$class = 'Wikibase\StatementObject';
			$isStatement = true;
		}
		elseif ( $entity instanceof ClaimListAccess ) {
			$class = 'Wikibase\ClaimObject';
		}
		else {
			throw new MWException( 'Entity does not support adding Claim objects' );
		}

		/**
		 * @var Claim $claim
		 */
		$claim = new $class( $snak );

		if ( $isStatement ) {
			$claim->setGuid( Utils::getGuid() );
			$entity->addStatement( $claim );
		}
		else {
			$entity->addClaim( $claim );
		}

		$baseRevisionId = isset( $params['baserevid'] ) ? intval( $params['baserevid'] ) : null;
		$baseRevisionId = $baseRevisionId > 0 ? $baseRevisionId : false;
		$editEntity = new EditEntity( $entityContent, $this->getUser(), $baseRevisionId );

		$editEntity->attemptSave(
			Autocomment::buildApiSummary( $this, null, $entityContent ),
			EDIT_UPDATE,
			isset( $params['token'] ) ? $params['token'] : false
		);

		return $claim;
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
			$this->dieUsage( 'A property ID needs to be provided when creating a claim with a PropertySnak', 'claim-property-id-missing' );
		}
	}

	/**
	 * @since 0.2
	 *
	 * @return EntityContent|null
	 */
	protected function getEntityContent() {
		$params = $this->extractRequestParams();

		$entityContent = EntityContentFactory::singleton()->getFromPrefixedId( $params['id'] );

		return $entityContent === null ? null : $entityContent;
	}

	/**
	 * @since 0.2
	 *
	 * @return Snak
	 * @throws MWException
	 */
	protected function getSnakInstance() {
		$params = $this->extractRequestParams();

		$entityFactory = EntityFactory::singleton();

		$propertyId = $entityFactory->getUnprefixedId( $params['property'] );

		switch ( $params['snaktype'] ) {
			case 'value':
				$dataValue = new \DataValues\StringValue( '' ); // TODO
				$snak = new PropertyValueSnak( $propertyId, $dataValue );
				break;
			case 'novalue':
				$snak = new PropertyNoValueSnak( $propertyId );
				break;
			case 'somevalue':
				$snak = new PropertySomeValueSnak( $propertyId );
				break;
		}

		if ( !isset( $snak ) ) {
			throw new MWException( '$snak was not set to an instance of Snak' );
		}

		return $snak;
	}

	/**
	 * @see ApiBase::getAllowedParams
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'id' => array(
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
		);
	}

	/**
	 * @see ApiBase::getParamDescription
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'id' => 'Id of the entity you are adding the statement to',
			'property' => 'Id of the property when creating a claim with a snak consisting of a property',
			'value' => 'Value of the snak when creating a claim with a snak that has a value',
			'snaktype' => 'The type of the snak',
			'token' => 'An "edittoken" token previously obtained through the token module (prop=info).',
			'baserevid' => array( 'The numeric identifier for the revision to base the modification on.',
				"This is used for detecting conflicts during save."
			),
		);
	}

	/**
	 * @see ApiBase::getDescription
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
	 * @see ApiBase::getExamples
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			// 'ex' => 'desc'
		);
	}

	/**
	 * @see ApiBase::getHelpUrls
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbcreateclaim';
	}

	/**
	 * @see ApiBase::getVersion
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . '-' . WB_VERSION;
	}

}
