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
 * @ingroup WikibaseLib
 *
 * @author Daniel Kinzler
 */


namespace Wikibase;

/**
 * Class DummyPropertyInfoStore is an implementation of PropertyInfoStore
 * that does nothing.
 *
 * @since 0.4
 *
 * @package Wikibase
 */
class DummyPropertyInfoStore implements PropertyInfoStore {

	/**
	 * @see   PropertyInfoStore::getPropertyInfo
	 *
	 * @param EntityId $propertyId
	 *
	 * @return null
	 */
	public function getPropertyInfo( EntityId $propertyId ) {
		return null;
	}

	/**
	 * @see   PropertyInfoStore::getAllPropertyInfo
	 *
	 * @return array[]
	 */
	public function getAllPropertyInfo() {
		return array();
	}

	/**
	 * @see PropertyInfoStore::setPropertyInfo
	 *
	 * @param EntityId $propertyId
	 * @param array    $info
	 */
	public function setPropertyInfo( EntityId $propertyId, array $info ) {
		// noop
	}

	/**
	 * @see   PropertyInfoStore::removePropertyInfo
	 *
	 * @param EntityId $propertyId
	 *
	 * @return bool false
	 */
	public function removePropertyInfo( EntityId $propertyId ) {
		return false;
	}
}