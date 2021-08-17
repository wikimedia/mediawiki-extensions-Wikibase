<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * PropertyDataTypeLookup that provides in-process caching.
 *
 * @since 3.1
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InProcessCachingDataTypeLookup implements PropertyDataTypeLookup {

	/**
	 * @var string[] Indexed by serialized PropertyId
	 */
	private $propertyIds = [];

	private $lookup;

	public function __construct( PropertyDataTypeLookup $propertyDataTypeLookup ) {
		$this->lookup = $propertyDataTypeLookup;
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
			$this->propertyIds[$serializedId] = $this->lookup->getDataTypeIdForProperty( $propertyId );
		}

		return $this->propertyIds[$serializedId];
	}

}
