<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * Check if a PropertyId is for a Property with a specific data type.
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
		try {
			return $this->findDataTypeIdForProperty( $propertyId ) === $dataType;
		} catch ( PropertyDataTypeLookupException $ex ) {
			return false;
		}
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 * @throws PropertyDataTypeLookupException
	 */
	private function findDataTypeIdForProperty( PropertyId $propertyId ) {
		return $this->propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId );
	}

}
