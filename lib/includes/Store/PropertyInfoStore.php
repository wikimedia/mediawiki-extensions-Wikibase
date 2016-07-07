<?php

namespace Wikibase;

use DBError;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\StorageException;

/**
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface PropertyInfoStore {

	/**
	 * Key to use in the info array for the property's data type ID.
	 */
	const KEY_DATA_TYPE = 'type';

	/**
	 * Key to use in the info array for the property's formatter URL pattern
	 */
	const KEY_FORMATTER_URL = 'formatterURL';

	/**
	 * Key to use in the info array for the property's canonical URI pattern
	 */
	const KEY_CANONICAL_URI = 'canonicalURI';

	/**
	 * Returns the property info for the given property ID.
	 *
	 * @note: Even if the property is known to exist, this method may not return
	 *        an info array, or the info array may not contain all well known fields.
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return array|null
	 * @throws StorageException
	 * @throws DBError
	 */
	public function getPropertyInfo( PropertyId $propertyId );

	/**
	 * Returns the property info for all properties with the given data type.
	 *
	 * @note: There is no guarantee that an info array is returned for all existing properties.
	 *        Also, it is not guaranteed that the info arrays will contain all well known fields.
	 *
	 * @param string $dataType
	 *
	 * @return array[] An associative array mapping property IDs to info arrays.
	 * @throws StorageException
	 * @throws DBError
	 */
	public function getPropertyInfoForDataType( $dataType );

	/**
	 * Returns the property info for all properties.
	 * The caller is responsible for avoiding calling this if there are too many properties.
	 *
	 * @note: There is no guarantee that an info array is returned for all existing properties.
	 *        Also, it is not guaranteed that the info arrays will contain all well known fields.
	 *
	 * @return array[] An associative array mapping property IDs to info arrays.
	 * @throws StorageException
	 * @throws DBError
	 */
	public function getAllPropertyInfo();

	/**
	 * Update the info for the given property.
	 *
	 * @note: All well known fields MUST be set in $info.
	 *
	 * @param PropertyId $propertyId
	 * @param array $info
	 *
	 * @throws StorageException
	 * @throws DBError
	 */
	public function setPropertyInfo( PropertyId $propertyId, array $info );

	/**
	 * Remove the info entry for the given property.
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return bool true iff something was deleted
	 * @throws StorageException
	 * @throws DBError
	 */
	public function removePropertyInfo( PropertyId $propertyId );

}
