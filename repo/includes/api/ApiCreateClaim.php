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
class ApiCreateClaim extends ApiBase {

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.2
	 */
	public function execute() {
		$this->checkParameterRequirements();

		$entity = $this->getEntity();

		if ( $entity === null ) {
			$this->dieUsage( 'Entity not found, snak not created', 'entity-not-found' );
		}

		$snak = $this->getSnakInstance();

		$claim = new ClaimObject( $snak );

		if ( $entity->getType() === Item::ENTITY_TYPE ) {
			$entity->addStatement( new StatementObject( $claim ) );
		}
		else {
			$entity->addClaim( $claim );
		}

		// TODO: save entity
		// TODO: add claim reference
		// TODO: transform claim to API output

		$this->getResult()->addValue(
			null,
			'claim',
			serialize( $claim )
		);
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
	 * @return Entity|null
	 */
	protected function getEntity() {
		$params = $this->extractRequestParams();

		$entityContent = EntityContentFactory::singleton()->getFromPrefixedId( $params['entity'] );

		return $entityContent === null ? null : $entityContent->getEntity();
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

		if ( in_array( $params['snaktype'], array( 'instance', 'subclass' ) ) ) {
			$itemId = $entityFactory->getUnprefixedId( $params['item'] );

			switch ( $params['snaktype'] ) {
				case 'instance':
					$snak = new InstanceOfSnak( $itemId );
					break;
				case 'subclass':
					$snak = new SubclassOfSnak( $itemId );
					break;
			}
		}
		else {
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
			'entity' => 'Id of the entity you are adding the statement to',
			'property' => 'Id of the property when creating a claim with a snak consisting of a property',
			'value' => 'Value of the snak when creating a claim with a snak that has a value',
			'snaktype' => 'The type of the snak',
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
