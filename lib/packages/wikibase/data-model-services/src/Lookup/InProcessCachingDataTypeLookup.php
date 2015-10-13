<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * PropertyDataTypeLookup that provides in-process caching.
 *
 * @since 3.1
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InProcessCachingDataTypeLookup implements PropertyDataTypeLookup {

	/**
	 * @var string[] Indexed by serialized PropertyId
	 */
	private $propertyIds = array();

	/**
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 */
	public function __construct( PropertyDataTypeLookup $propertyDataTypeLookup ) {
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 * @throws PropertyDataTypeLookupException
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId ) {
		$serializedId = $propertyId->getSerialization();

		if ( !array_key_exists( $serializedId, $this->propertyIds ) ) {
			$dataTypeId = $this->propertyDataTypeLookup->getDataTypeIdFOrProperty( $propertyId );
			$this->propertyIds[$serializedId] = $dataTypeId;
		}

		return $this->propertyIds[$serializedId];
	}

}
