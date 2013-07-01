<?php
 /**
 *
 * Copyright © 26.06.13 by the authors listed below.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @license GPL 2+
 * @file
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @author Daniel Kinzler
 */


namespace Wikibase\Test;

use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\PropertyInfoStore;

/**
 * Class MockPropertyInfoStore is an implementation of PropertyInfoStore
 * based on a local array.
 *
 * @since 0.4
 *
 * @package Wikibase\Test
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
	 * @throws \InvalidArgumentException
	 */
	public function getPropertyInfo( EntityId $propertyId ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new \InvalidArgumentException( 'Property ID expected! ' . $propertyId );
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
	 * @param array    $info
	 */
	public function setPropertyInfo( EntityId $propertyId, array $info ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new \InvalidArgumentException( 'Property ID expected! ' . $propertyId );
		}

		if ( !isset( $info[ PropertyInfoStore::KEY_DATA_TYPE ]) ) {
			throw new \InvalidArgumentException( 'Missing required info field: ' . PropertyInfoStore::KEY_DATA_TYPE );
		}

		$id = $propertyId->getNumericId();
		$this->propertyInfo[$id] = $info;
	}

	/**
	 * @see   PropertyInfoStore::removePropertyInfo
	 *
	 * @param EntityId $propertyId
	 *
	 * @return bool
	 */
	public function removePropertyInfo( EntityId $propertyId ) {
		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new \InvalidArgumentException( 'Property ID expected! ' . $propertyId );
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