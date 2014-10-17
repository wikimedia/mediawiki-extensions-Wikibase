<?php

namespace Wikibase\Content;

use RuntimeException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;

/**
 * A holder for entities.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface EntityHolder {

	/**
	 * Returns the entity held by this EntityHolder.
	 * Depending on the implementation, this operation may be expensive or trivial.
	 *
	 * @param string $expectedClass The class the result is expected to be compatible with.
	 * Defaults to Entity.
	 *
	 * @throws RuntimeException If the entity held by this EntityHolder is not compatible with $expectedClass.
	 * @return Entity
	 */
	public function getEntity( $expectedClass = 'Wikibase\DataModel\Entity\Entity' );

	/**
	 * Returns the ID of the entity held by this EntityHolder.
	 * May or may not require the actual entity to be instantiated.
	 * May be null if the Entity does not have an ID set.
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
 