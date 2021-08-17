<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * PropertyDataTypeLookup that uses an EntityLookup to find
 * a property's data type ID.
 *
 * @since 1.1
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityRetrievingDataTypeLookup implements PropertyDataTypeLookup {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	public function __construct( EntityLookup $entityLookup ) {
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @since 2.0
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 * @throws PropertyDataTypeLookupException
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId ) {
		return $this->getProperty( $propertyId )->getDataTypeId();
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return Property
	 * @throws PropertyDataTypeLookupException
	 */
	private function getProperty( PropertyId $propertyId ) {
		$property = $this->entityLookup->getEntity( $propertyId );

		if ( !( $property instanceof Property ) ) {
			throw new PropertyDataTypeLookupException( $propertyId );
		}

		return $property;
	}

}
