<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
interface EntityTermStoreWriter {

	/**
	 * Saves the terms of the provided entity in the term store.
	 *
	 * @param EntityDocument $entity
	 *
	 * @return boolean true on success, false otherwise.
	 */
	public function saveTerms( EntityDocument $entity );

	/**
	 * Deletes the terms of the provided entity from the term store.
	 *
	 * @param EntityId $entityId
	 *
	 * @return boolean true on success, false otherwise.
	 */
	public function deleteTerms( EntityId $entityId );

}
