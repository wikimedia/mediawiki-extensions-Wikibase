<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\PropertyId;
use Wikimedia\Rdbms\DBError;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface PropertyInfoStore {

	/**
	 * Key to use in the info array for the property's canonical URI pattern
	 */
	const KEY_CANONICAL_URI = 'canonicalURI';

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
