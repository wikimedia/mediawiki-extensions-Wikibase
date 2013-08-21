<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use Wikibase\EntityId;
use Wikibase\Property;

/**
 * PropertyDataTypeLookup that uses an in memory array to retrieve the requested information.
 * If the information is not set when requested an exception is thrown.
 * This class can be used as a mock in tests.
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

class InMemoryDataTypeLookup implements PropertyDataTypeLookup {

	private $dataTypeIds = array();

	/**
	 * @since 0.4
	 *
	 * @param EntityId $propertyId
	 *
	 * @return string
	 * @throws PropertyNotFoundException
	 */
	public function getDataTypeIdForProperty( EntityId $propertyId ) {
		$this->verifyIdIsOfAProperty( $propertyId );
		$this->verifyDataTypeIsSet( $propertyId );

		return $this->dataTypeIds[$propertyId->getNumericId()];
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $propertyId
	 * @param string $dataTypeId
	 */
	public function setDataTypeForProperty( EntityId $propertyId, $dataTypeId ) {
		$this->verifyIdIsOfAProperty( $propertyId );
		$this->verifyDataTypeIdType( $dataTypeId );
		$this->dataTypeIds[$propertyId->getNumericId()] = $dataTypeId;
	}

	private function verifyDataTypeIsSet( EntityId $propertyId ) {
		$numericId = $propertyId->getNumericId();

		if ( !array_key_exists( $numericId, $this->dataTypeIds ) ) {
			throw new PropertyNotFoundException( $propertyId, "The DataType for property '$numericId' is not set" );
		}
	}

	private function verifyDataTypeIdType( $dataTypeId ) {
		if ( !is_string( $dataTypeId ) ) {
			throw new InvalidArgumentException( '$dataTypeId needs to be a string' );
		}
	}

	private function verifyIdIsOfAProperty( EntityId $propertyId ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new InvalidArgumentException( '$propertyId with non-property entity type provided' );
		}
	}

}
