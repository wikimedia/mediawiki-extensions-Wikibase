<?php

namespace Wikibase\DataModel\Entity;

use Comparable;
use InvalidArgumentException;
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

	/**
	 * Construct a derivative such as ItemId or PropertyId directly.
	 * In the long term this class is meant to become abstract.
	 *
	 * The second argument, $idSerialization, should be the entire
	 * id serialization. For compatibility reasons this also accepts
	 * the numeric part for item and property ids. This is however
	 * highly deprecated.
	 *
	 * Derivatives are allowed (and required) to use this constructor.
	 *
	 * @param string $entityType
	 * @param string|int $idSerialization
	 *
	 * @throws InvalidArgumentException
	 */
	protected function __construct( $entityType, $idSerialization ) {
		$this->setEntityType( $entityType );
		$this->setIdSerialization( $idSerialization );
	}

	private function setEntityType( $entityType ) {
		if ( !is_string( $entityType ) ) {
			throw new InvalidArgumentException( '$entityType needs to be a string' );
		}

		$this->entityType = $entityType;
	}

	private function setIdSerialization( $idSerialization ) {
		if ( is_string( $idSerialization ) ) {
			$this->serialization = strtoupper( $idSerialization );
		} elseif ( is_int( $idSerialization ) ) {
			$this->serialization = LegacyIdInterpreter::newIdFromTypeAndNumber(
				$this->entityType,
				$idSerialization
			)->getSerialization();
		} else {
			throw new InvalidArgumentException( '$idSerialization needs to be a string' );
		}
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
		list( $entityType, $serialization ) = json_decode( $value );

		// Compatibility with < 0.5.
		// Numeric ids where stored in the serialization.
		// Pass explicitly as int, so it is recognized properly.
		if ( ctype_digit( $serialization ) ) {
			$serialization = (int)$serialization;
		}

		self::__construct( $entityType, $serialization );
	}

}
