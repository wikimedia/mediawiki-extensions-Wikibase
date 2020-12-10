<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\PropertyId;
use Wikimedia\Rdbms\DBError;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyInfoLookup {

	/**
	 * Key to use in the info array for the property's data type ID.
	 */
	public const KEY_DATA_TYPE = 'type';

	/**
	 * Key to use in the info array for the property's formatter URL
	 */
	public const KEY_FORMATTER_URL = 'formatterURL';

	/**
	 * Returns the property info for the given property ID.
	 *
	 * @note Even if the property is known to exist, this method may not return
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
	 * @note There is no guarantee that an info array is returned for all existing properties.
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
	 * @note There is no guarantee that an info array is returned for all existing properties.
	 *        Also, it is not guaranteed that the info arrays will contain all well known fields.
	 *
	 * @return array[] An associative array mapping property IDs to info arrays.
	 * @throws StorageException
	 * @throws DBError
	 */
	public function getAllPropertyInfo();

}
