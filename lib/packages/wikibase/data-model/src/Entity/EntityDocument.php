<?php

namespace Wikibase\DataModel\Entity;

/**
 * Minimal interface for all objects that represent an entity.
 * All entities have an EntityId and an entity type.
 *
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface EntityDocument {

	/**
	 * Returns the id of the entity or null if it does not have one.
	 *
	 * @since 1.0
	 *
	 * @return EntityId|null
	 */
	public function getId();

	/**
	 * Returns a type identifier for the entity.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function getType();

}