<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\PropertyInfoStore;

/**
 * Class MockPropertyInfoStore is an implementation of PropertyInfoStore based on a local array.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MockPropertyInfoStore implements PropertyInfoStore {

	/**
	 * Maps properties to info arrays
	 *
	 * @var array[]
	 */
	protected $propertyInfo = [];

	/**
	 * @see PropertyInfoStore::getPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 * @throws InvalidArgumentException
	 */
	public function getPropertyInfo( PropertyId $propertyId ) {
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
	 * @see PropertyInfoStore::getPropertyInfoForDataType
	 *
	 * @param string $dataType
	 *
	 * @return array[]
	 */
	public function getPropertyInfoForDataType( $dataType ) {
		$propertyInfo = $this->getAllPropertyInfo();
		$propertyInfoForDataType = [];

		foreach ( $propertyInfo as $id => $info ) {
			if ( $info[PropertyInfoStore::KEY_DATA_TYPE] === $dataType ) {
				$propertyInfoForDataType[$id] = $info;
			}
		}

		return $propertyInfoForDataType;
	}

	/**
	 * @see PropertyInfoStore::getAllPropertyInfo
	 *
	 * @return array[]
	 */
	public function getAllPropertyInfo() {
		return $this->propertyInfo;
	}

	/**
	 * @see PropertyInfoStore::setPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 * @param array $info
	 * @throws InvalidArgumentException
	 */
	public function setPropertyInfo( PropertyId $propertyId, array $info ) {
		if ( !isset( $info[PropertyInfoStore::KEY_DATA_TYPE] ) ) {
			throw new InvalidArgumentException( 'Missing required info field: ' . PropertyInfoStore::KEY_DATA_TYPE );
		}

		$id = $propertyId->getNumericId();
		$this->propertyInfo[$id] = $info;
	}

	/**
	 * @see PropertyInfoStore::removePropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	public function removePropertyInfo( PropertyId $propertyId ) {
		$id = $propertyId->getNumericId();

		if ( array_key_exists( $id, $this->propertyInfo ) ) {
			unset( $this->propertyInfo[$id] );
			return true;
		} else {
			return false;
		}
	}

}
