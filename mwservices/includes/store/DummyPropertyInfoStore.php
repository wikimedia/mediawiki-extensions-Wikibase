<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * Class DummyPropertyInfoStore is an implementation of PropertyInfoStore
 * that does nothing.
 *
 * @since 0.4
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class DummyPropertyInfoStore implements PropertyInfoStore {

	/**
	 * @see PropertyInfoStore::getPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return null
	 */
	public function getPropertyInfo( PropertyId $propertyId ) {
		return null;
	}

	/**
	 * @see PropertyInfoStore::getAllPropertyInfo
	 *
	 * @return array[]
	 */
	public function getAllPropertyInfo() {
		return array();
	}

	/**
	 * @see PropertyInfoStore::setPropertyInfo
	 *
	 * @param PropertyId $propertyId
	 * @param array $info
	 */
	public function setPropertyInfo( PropertyId $propertyId, array $info ) {
		// noop
	}

	/**
	 * @see PropertyInfoStore::removePropertyInfo
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return bool false
	 */
	public function removePropertyInfo( PropertyId $propertyId ) {
		return false;
	}

}