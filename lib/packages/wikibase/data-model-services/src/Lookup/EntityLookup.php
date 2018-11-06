<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for retrieving Entities from storage.
 *
 * @since 1.1
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
interface EntityLookup {

	/**
	 * @note Implementations of this method may or may not resolve redirects.
	 * Code that needs control over redirect resolution should use an
	 * EntityRevisionLookup instead.
	 *
	 * @since 2.0
	 *
	 * @param EntityId $entityId
	 *
	 * @return EntityDocument|null
	 * @throws EntityLookupException
	 */
	public function getEntity( EntityId $entityId );

	/**
	 * Returns whether the given entity can bee looked up using
	 * getEntity(). This avoids loading and deserializing entity content
	 * just to check whether the entity exists.
	 *
	 * @note Implementations of this method may or may not resolve redirects.
	 * Code that needs control over redirect resolution should use an
	 * EntityRevisionLookup instead.
	 *
	 * @since 1.1
	 *
	 * @param EntityId $entityId
	 *
	 * @return bool
	 * @throws EntityLookupException
	 */
	public function hasEntity( EntityId $entityId );

}
