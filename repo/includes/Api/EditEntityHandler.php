<?php

namespace Wikibase\Api;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * Interface to handle specific entity types for the wbeditentity api module.
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface EditEntityHandler {

	/**
	 * Create a new empty entity based on the data provided in the array.
	 * Note that the returned entity must not have an id set.
	 *
	 * @param array $data
	 *
	 * @return EntityDocument
	 */
	public function createEntityFromData( array $data );

	/**
	 * Creates a new empty entity based on the provided, possible non-empty entity.
	 * Note that the returned entity must have the same id as the provided one.
	 *
	 * @param EntityDocument $entity
	 *
	 * @return EntityDocument
	 */
	public function createEmptyEntity( EntityDocument $entity );

}
