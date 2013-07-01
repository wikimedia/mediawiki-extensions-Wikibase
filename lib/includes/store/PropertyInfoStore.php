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


use DBError;

interface PropertyInfoStore {

	/**
	 * Key to use in the info array for the property's data type ID.
	 */
	const KEY_DATA_TYPE = 'type';

	/**
	 * Returns the property info for the given property ID.
	 *
	 * @note: Even if the property is known to exist, this method may not return
	 *        an info array, or the info array may not contain all well known fields.
	 *
	 * @param EntityId $propertyId
	 *
	 * @return array|null
	 *
	 * @throws StorageException
	 * @throws DBError
	 */
	public function getPropertyInfo( EntityId $propertyId );

	/**
	 * Returns the property info for all properties.
	 * The caller is responsible for avoiding calling this if there are too many properties.
	 *
	 * @note: There is no guarantee that an info array is returned for all existing properties.
	 *        Also, it is not guaranteed that the ionfo arrays will contain all well known fields.
	 *
	 * @return array[] An associative array mapping property IDs to info arrays.
	 *
	 * @throws StorageException
	 * @throws \DBError
	 */
	public function getAllPropertyInfo();

	/**
	 * Update the info for the given property.
	 *
	 * @note: All well known fields MUST be set in $info.
	 *
	 * @param EntityId $propertyId
	 * @param array    $info
	 *
	 * @throws StorageException
	 * @throws DBError
	 */
	public function setPropertyInfo( EntityId $propertyId, array $info );

	/**
	 * Remove the info entry for the given property.
	 *
	 * @param EntityId $propertyId
	 *
	 * @return bool true iff something was deleted
	 *
	 * @throws StorageException
	 * @throws DBError
	 */
	public function removePropertyInfo( EntityId $propertyId );

}