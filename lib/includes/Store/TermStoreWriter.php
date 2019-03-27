<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface to a store for terms with write methods.
 *
 * @license GPL-2.0-or-later
 */
interface TermStoreWriter {

	/**
	 * Saves the terms of the provided entity in the term store.
	 *
	 * @param EntityDocument $entity Must have an ID, and optionally any combination of terms as
	 *  declared by the TermIndexEntry::TYPE_... constants.
	 *
	 * @throws \InvalidArgumentException when $entity does not have an ID.
	 * @return boolean Success indicator
	 */
	public function saveTermsOfEntity( EntityDocument $entity );

	/**
	 * Deletes the terms of the provided entity from the term store.
	 *
	 * @param EntityId $entityId
	 *
	 * @return boolean Success indicator
	 */
	public function deleteTermsOfEntity( EntityId $entityId );

}
