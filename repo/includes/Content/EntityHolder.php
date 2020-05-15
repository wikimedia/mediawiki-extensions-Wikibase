<?php

namespace Wikibase\Repo\Content;

use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * A holder for entity objects.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface EntityHolder {

	/**
	 * Returns the entity held by this EntityHolder.
	 * Depending on the implementation, this operation may be expensive or trivial.
	 *
	 * @param string $expectedClass The class with which the result is expected to be compatible.
	 * Defaults to EntityDocument.
	 *
	 * @throws RuntimeException If the entity held by this EntityHolder is not compatible with $expectedClass.
	 * @return EntityDocument
	 */
	public function getEntity( $expectedClass = EntityDocument::class );

	/**
	 * Returns the ID of the entity held by this EntityHolder.
	 * May or may not require the actual entity to be instantiated.
	 * May be null if the entity does not have an ID set.
	 *
	 * @return EntityId|null
	 */
	public function getEntityId();

	/**
	 * Returns the type of the entity held by this EntityHolder.
	 * May or may not require the actual entity or the entity's ID to be instantiated.
	 * Implementations must make sure that this is never null.
	 *
	 * @return string
	 */
	public function getEntityType();

}
