<?php

namespace Wikibase;

use DBError;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\StorageException;

/**
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface PropertyInfoStore extends PropertyInfoLookup {

	/**
	 * Key to use in the info array for the property's data type ID.
	 */
	const KEY_DATA_TYPE = 'type';

	/**
	 * Key to use in the info array for the property's formatter URL
	 */
	const KEY_FORMATTER_URL = 'formatterURL';

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
