<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;

/**
 * Minimal interface for all objects that represent an entity. All entities have an entity type and
 * an EntityId.
 *
 * @since 0.8.2
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface EntityDocument {

	/**
	 * Returns a type identifier for the entity, e.g. "item" or "property".
	 *
	 * @since 0.8.2
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * Returns the id of the entity or null if it does not have one.
	 *
	 * @since 0.8.2
	 *
	 * @return EntityId|null
	 */
	public function getId();

	/**
	 * Sets the id of the entity. A specific derivative of EntityId is always supported.
	 *
	 * @since 3.0
	 *
	 * @param EntityId $id
	 *
	 * @throws InvalidArgumentException if the id is not of the correct type.
	 */
	public function setId( $id );

	/**
	 * An entity is considered empty if it does not contain any content that can be removed. Having
	 * an ID set never counts as having content.
	 *
	 * Knowing if an entity is empty is relevant when, for example, moving or merging entities and
	 * code wants to make sure all content is transferred from the old to the new entity.
	 *
	 * @since 4.3
	 *
	 * @return bool
	 */
	public function isEmpty();

	/**
	 *
	 * Two entities are considered equal if they are of the same type and have the same value. The
	 * value does not include the id, so entities with the same value but different id are
	 * considered equal.
	 *
	 * @since 5.0
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target );

	/**
	 * Returns a deep clone of the entity. The clone must be equal in all details, including the id.
	 * No change done to the clone is allowed to interfere with the original object. Only properties
	 * containing immutable objects are allowed to (and should) reference the original object.
	 *
	 * Since EntityDocuments are mutable (at least the id can be set) the method is not allowed to
	 * return $this.
	 *
	 * @since 5.0
	 *
	 * @return self
	 */
	public function copy();

}
