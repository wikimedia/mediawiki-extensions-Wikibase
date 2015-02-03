<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;

/**
 * Minimal interface for all objects that represent an entity.
 * All entities have an EntityId and an entity type.
 *
 * @since 0.8.2, modified in 3.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface EntityDocument {

	/**
	 * Returns the id of the entity or null if it does not have one.
	 *
	 * @since 0.8.2
	 *
	 * @return EntityId|null
	 */
	public function getId();

	/**
	 * Returns a type identifier for the entity.
	 *
	 * @since 0.8.2
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * Sets the id of the entity. A specific derivative of EntityId is always supported.
	 *
	 * @since 3.0
	 *
	 * @throws InvalidArgumentException if the id is not of the correct type.
	 */
	public function setId( $id );

}
