<?php

namespace Wikibase\Repo\SeaHorse;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * A basic entity that just takes any string.
 */
class SeaHorse implements EntityDocument {

	private $id;
	private $content;

	public function __construct(SeaHorseId $id = null, string $content = '') {
		$this->id = $id;
		$this->content = $content;
	}

	/**
	 * Returns a type identifier for the entity, e.g. "item" or "property".
	 * @return string
	 */
	public function getType() {
		return 'sea-horse-entity-type';
	}

	/**
	 * Returns the id of the entity or null if it does not have one.
	 * @return EntityId|null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Sets the id of the entity. A specific derivative of EntityId is always supported.
	 * @param EntityId $id
	 *
	 * @throws \InvalidArgumentException if the id is not of the correct type.
	 */
	public function setId( $id ) {
		if ( $id instanceof SeaHorseId ) {
			$this->id = $id;
		} else {
			throw new \InvalidArgumentException( 'Invalid id type' );
		}
	}

	/**
	 * An entity is considered empty if it does not contain any content that can be removed. Having
	 * an ID set never counts as having content.
	 *
	 * Knowing if an entity is empty is relevant when, for example, moving or merging entities and
	 * code wants to make sure all content is transferred from the old to the new entity.
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return $this->content === '';
	}

	/**
	 *
	 * Two entities are considered equal if they are of the same type and have the same value. The
	 * value does not include the id, so entities with the same value but different id are
	 * considered equal.
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		return $target instanceof self && $this->content === $target->content;
	}

	/**
	 * Returns a deep clone of the entity. The clone must be equal in all details, including the id.
	 * No change done to the clone is allowed to interfere with the original object. Only properties
	 * containing immutable objects are allowed to (and should) reference the original object.
	 *
	 * Since EntityDocuments are mutable (at least the id can be set) the method is not allowed to
	 * return $this.
	 *
	 * @return self
	 */
	public function copy() {
		return new self( clone $this->id, $this->content );
	}

	public function getContent() {
		return $this->content;
	}

}
