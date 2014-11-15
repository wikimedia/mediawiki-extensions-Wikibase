<?php

namespace Wikibase\Client\Store;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\StorageException;

/**
 * Service interface for looking up EntityIds given local wiki pages.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 */
interface EntityIdLookup {

	/**
	 * Returns the list of EntityIds of the entities associated with the
	 * given page titles. The resulting array uses the page IDs as keys,
	 *
	 * @param Title[] $titles
	 *
	 * @throws StorageException
	 * @return EntityId[] Entity IDs, keyed by page IDs.
	 */
	public function getEntityIds( array $titles );

}