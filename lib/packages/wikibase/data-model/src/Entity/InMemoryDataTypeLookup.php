<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;

/**
 * PropertyDataTypeLookup that uses an in memory array to retrieve the requested information.
 * If the information is not set when requested an exception is thrown.
 * This class can be used as a fake in tests.
 *
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class InMemoryDataTypeLookup implements PropertyDataTypeLookup {

	private $dataTypeIds = array();

	/**
	 * @since 0.4
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 * @throws PropertyNotFoundException
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId ) {
		$this->verifyDataTypeIsSet( $propertyId );

		return $this->dataTypeIds[$propertyId->getSerialization()];
	}

	/**
	 * @since 0.4
	 *
	 * @param PropertyId $propertyId
	 * @param string $dataTypeId
	 */
	public function setDataTypeForProperty( PropertyId $propertyId, $dataTypeId ) {
		$this->verifyDataTypeIdType( $dataTypeId );
		$this->dataTypeIds[$propertyId->getSerialization()] = $dataTypeId;
	}

	private function verifyDataTypeIsSet( PropertyId $propertyId ) {
		$numericId = $propertyId->getSerialization();

		if ( !array_key_exists( $numericId, $this->dataTypeIds ) ) {
			throw new PropertyNotFoundException( $propertyId, "The DataType for property '$numericId' is not set" );
		}
	}

	private function verifyDataTypeIdType( $dataTypeId ) {
		if ( !is_string( $dataTypeId ) ) {
			throw new InvalidArgumentException( '$dataTypeId needs to be a string' );
		}
	}

}
