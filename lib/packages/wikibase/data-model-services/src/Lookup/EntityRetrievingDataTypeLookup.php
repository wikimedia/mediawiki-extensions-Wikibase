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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityRetrievingDataTypeLookup implements PropertyDataTypeLookup {

	private $entityLookup;

	public function __construct( EntityLookup $entityLookup ) {
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @since 0.4
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 * @throws EntityIdLookupException
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId ) {
		return $this->getProperty( $propertyId )->getDataTypeId();
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return Property
	 * @throws EntityIdLookupException
	 */
	private function getProperty( PropertyId $propertyId ) {
		$property = $this->entityLookup->getEntity( $propertyId );

		if ( !( $property instanceof Property ) ) {
			throw new EntityIdLookupException( $propertyId );
		}

		return $property;
	}

}
