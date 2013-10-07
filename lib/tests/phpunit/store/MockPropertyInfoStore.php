<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\PropertyInfoStore;

/**
 * Class MockPropertyInfoStore is an implementation of PropertyInfoStore based on a local array.
 * @since 0.4
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class MockPropertyInfoStore implements PropertyInfoStore {

	/**
	 * Maps properties to info arrays
	 *
	 * @var array[]
	 */
	protected $propertyInfo = array();

	/**
	 * @see   PropertyInfoStore::getPropertyInfo
	 *
	 * @param EntityId $propertyId
	 *
	 * @return array|null
	 * @throws InvalidArgumentException
	 */
	public function getPropertyInfo( EntityId $propertyId ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new InvalidArgumentException( 'Property ID expected! ' . $propertyId );
		}

		$propertyInfo = $this->getAllPropertyInfo();
		$id = $propertyId->getNumericId();

		if ( isset( $propertyInfo[$id] ) ) {
			return $propertyInfo[$id];
		}

		return null;
	}

	/**
	 * @see   PropertyInfoStore::getAllPropertyInfo
	 *
	 * @return array[]
	 */
	public function getAllPropertyInfo() {
		return $this->propertyInfo;
	}

	/**
	 * @see PropertyInfoStore::setPropertyInfo
	 *
	 * @param EntityId $propertyId
	 * @param array $info
	 * @throws InvalidArgumentException
	 */
	public function setPropertyInfo( EntityId $propertyId, array $info ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new InvalidArgumentException( 'Property ID expected! ' . $propertyId );
		}

		if ( !isset( $info[ PropertyInfoStore::KEY_DATA_TYPE ]) ) {
			throw new InvalidArgumentException( 'Missing required info field: ' . PropertyInfoStore::KEY_DATA_TYPE );
		}

		$id = $propertyId->getNumericId();
		$this->propertyInfo[$id] = $info;
	}

	/**
	 * @see   PropertyInfoStore::removePropertyInfo
	 *
	 * @param EntityId $propertyId
	 *
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	public function removePropertyInfo( EntityId $propertyId ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new InvalidArgumentException( 'Property ID expected! ' . $propertyId );
		}

		$id = $propertyId->getNumericId();

		if ( array_key_exists( $id, $this->propertyInfo ) ) {
			unset( $this->propertyInfo[$id] );
			return true;
		} else {
			return false;
		}
	}
}