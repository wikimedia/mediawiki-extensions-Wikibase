<?php

namespace Wikibase\DataModel\Services\Entity;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;

/**
 * Check if a PropertyId is for a Property with a specific data type.
 *
 * @since 3.1
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyDataTypeMatcher {

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	public function __construct( PropertyDataTypeLookup $propertyDataTypeLookup ) {
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
	}

	/**
	 * Returns if the property with the specified property id has the provided data type.
	 *
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
