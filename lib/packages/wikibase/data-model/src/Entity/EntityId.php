<?php

namespace Wikibase\DataModel\Entity;

use Comparable;
use Serializable;
use Wikibase\DataModel\LegacyIdInterpreter;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com
 */
class EntityId implements Comparable, Serializable {

	protected $entityType;
	protected $serialization;

	private function __construct() {
	}

	/**
	 * @return string
	 */
	public function getEntityType() {
		return $this->entityType;
	}

	/**
	 * @return string
	 */
	public function getSerialization() {
		return $this->serialization;
	}

	/**
	 * Returns the id serialization.
	 * @deprecated Use getSerialization instead.
	 * (soft depreaction, this alias will stay untill it is no longer used)
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

	/**
	 * @see Serializable::serialize
	 *
	 * @return string
	 */
	public function serialize() {
		return json_encode( array( $this->entityType, $this->serialization ) );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @param string $value
	 */
	public function unserialize( $value ) {
		list( $this->entityType, $this->serialization ) = json_decode( $value );

		// Compatibility with < 0.5. Numeric ids where stored in the serialization.
		if ( is_int( $this->serialization ) || ctype_digit( $this->serialization ) ) {
			$this->serialization = LegacyIdInterpreter::newIdFromTypeAndNumber(
				$this->entityType,
				$this->serialization
			)->serialization;
		} else {
			$this->serialization = strtoupper( $this->serialization );
		}
	}

}
