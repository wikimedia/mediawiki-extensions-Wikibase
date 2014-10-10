<?php

namespace Wikibase\DataModel\Entity;

/**
 * @since 0.5
 * Constructor non-public since 1.0
 * Abstract since 2.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com
 */
abstract class EntityId implements \Comparable, \Serializable {

	protected $serialization;

	/**
	 * @return string
	 */
	public abstract function getEntityType();

	/**
	 * @return string
	 */
	public function getSerialization() {
		return $this->serialization;
	}

	/**
	 * Returns the id serialization.
	 * @deprecated Use getSerialization instead.
	 * (soft deprecation, this alias will stay until it is no longer used)
	 *
	 * @return string
	 */
	public function getPrefixedId() {
		return $this->serialization;
	}

	/**
	 * This is a human readable representation of the EntityId.
	 * This format is allowed to change and should therefore not
	 * be relied upon to be stable.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->serialization;
	}

	/**
	 * @see Comparable::equals
	 *
	 * @since 0.5
	 *
	 * @param mixed $target
	 *
	 * @return boolean
	 */
	public function equals( $target ) {
		return $target instanceof self
			&& $target->serialization === $this->serialization;
	}

}
