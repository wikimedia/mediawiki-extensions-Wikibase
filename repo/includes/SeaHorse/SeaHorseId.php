<?php

namespace Wikibase\Repo\SeaHorse;

use Wikibase\DataModel\Entity\EntityId;

/**
 * A basic ID that just takes any string.
 * Methods that may not be needed since the grand federated properties rework are just ignored for now.
 */
class SeaHorseId implements EntityId {

	private string $id;

	public function __construct( string $id ) {
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return SeaHorseSaddle::ENTITY_TYPE;
	}

	/**
	 * @return string
	 */
	public function getSerialization() {
		return $this->id;
	}

	/**
	 * TODO: Consider removing this method in favor of just always calling getSerialization().
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->id;
	}

	/**
	 * @param mixed $target
	 * @return bool
	 */
	public function equals( $target ) {
		return $target instanceof self && $this->id === $target->id;
	}

	/**
	 * TODO: This method shouldn't exist on this interface as it doesn't make sense for certain types of IDs. It should be moved to a
	 *       separate interface, or removed altogether.
	 *
	 * @return string
	 */
	public function getLocalPart() {
		throw new \Exception( 'Not implemented' );
	}

	/**
	 * TODO: This method shouldn't exist on this interface as it doesn't make sense for certain types of IDs. It should be moved to a
	 *       separate interface, or removed altogether.
	 *
	 * @return string
	 */
	public function getRepositoryName() {
		throw new \Exception( 'Not implemented' );
	}

	/**
	 * String representation of object
	 * Should return the string representation of the object.
	 *
	 * @return null|string Returns the string representation of the object or `null`
	 */
	public function serialize(): string {
		return $this->id;
	}

	/**
	 * Constructs the object
	 * Called during unserialization of the object.
	 *
	 * @param string $data The string representation of the object.
	 */
	public function unserialize($data): void {
		$this->id = $data;
	}

}
