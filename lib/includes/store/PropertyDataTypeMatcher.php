<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;

/**
 * Check if a PropertyId is for a Property with a specific data type.
 * As well, in-process caching of the lookups is done for performance reasons.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyDataTypeMatcher {

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var string[]
	 */
	private $propertyDataTypes = array();

	/**
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 */
	public function __construct( PropertyDataTypeLookup $propertyDataTypeLookup ) {
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
	}

	/**
	 * @param PropertyId $propertyId
	 * @param string $dataType
	 *
	 * @return bool
	 */
	public function isMatchingDataType( PropertyId $propertyId, $dataType ) {
		return $this->getDataTypeIdForProperty( $propertyId ) === $dataType;
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return string|null
	 */
	private function getDataTypeIdForProperty( PropertyId $propertyId ) {
		$prefixedId = $propertyId->getSerialization();

		if ( !array_key_exists( $prefixedId, $this->propertyDataTypes ) ) {
			$this->propertyDataTypes[$prefixedId] = $this->findDataTypeIdForProperty( $propertyId );
		}

		return $this->propertyDataTypes[$prefixedId];
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return string|null
	 */
	private function findDataTypeIdForProperty( PropertyId $propertyId ) {
		try {
			return $this->propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId );
		} catch ( PropertyDataTypeLookupException $ex ) {
			return null;
		}
	}

}
