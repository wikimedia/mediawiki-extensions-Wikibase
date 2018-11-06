<?php

namespace Wikibase\DataModel\Services\Lookup;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * PropertyDataTypeLookup that uses an in memory array to retrieve the requested information.
 * If the information is not set when requested an exception is thrown.
 * This class can be used as a fake in tests.
 *
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class InMemoryDataTypeLookup implements PropertyDataTypeLookup {

	/**
	 * @var string[]
	 */
	private $dataTypeIds = [];

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 * @throws PropertyDataTypeLookupException
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId ) {
		$this->verifyDataTypeIsSet( $propertyId );

		return $this->dataTypeIds[$propertyId->getSerialization()];
	}

	/**
	 * @since 1.0
	 *
	 * @param PropertyId $propertyId
	 * @param string $dataTypeId
	 *
	 * @throws InvalidArgumentException
	 */
	public function setDataTypeForProperty( PropertyId $propertyId, $dataTypeId ) {
		$this->verifyDataTypeIdType( $dataTypeId );
		$this->dataTypeIds[$propertyId->getSerialization()] = $dataTypeId;
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @throws PropertyDataTypeLookupException
	 */
	private function verifyDataTypeIsSet( PropertyId $propertyId ) {
		$id = $propertyId->getSerialization();

		if ( !array_key_exists( $id, $this->dataTypeIds ) ) {
			throw new PropertyDataTypeLookupException( $propertyId, "The DataType for property '$id' is not set" );
		}
	}

	/**
	 * @param string $dataTypeId
	 *
	 * @throws InvalidArgumentException
	 */
	private function verifyDataTypeIdType( $dataTypeId ) {
		if ( !is_string( $dataTypeId ) ) {
			throw new InvalidArgumentException( '$dataTypeId must be a string; got ' . gettype( $dataTypeId ) );
		}
	}

}
