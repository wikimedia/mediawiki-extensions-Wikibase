<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;

/**
 *
 * @license GPL-2.0-or-later
 */
abstract class SerializableEntityId implements EntityId {

	protected $serialization;

	public const PATTERN = '/^[^:]+\z/';

	/**
	 * @param string $serialization
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $serialization ) {
		self::assertValidSerialization( $serialization );
		$this->serialization = self::normalizeIdSerialization( $serialization );
	}

	private static function assertValidSerialization( $serialization ) {
		if ( !is_string( $serialization ) ) {
			throw new InvalidArgumentException( '$serialization must be a string' );
		}

		if ( $serialization === '' ) {
			throw new InvalidArgumentException( '$serialization must not be an empty string' );
		}

		if ( !preg_match( self::PATTERN, $serialization ) ) {
			throw new InvalidArgumentException( '$serialization must match ' . self::PATTERN );
		}
	}

	/**
	 * @return string
	 */
	abstract public function getEntityType();

	/**
	 * @return string
	 */
	public function getSerialization() {
		return $this->serialization;
	}

	/**
	 * @param string $id
	 *
	 * @return string
	 */
	private static function normalizeIdSerialization( $id ) {
		return ltrim( $id, ':' );
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
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		if ( $this === $target ) {
			return true;
		}

		return $target instanceof self
			&& $target->serialization === $this->serialization;
	}

	abstract public function __serialize(): array;

	abstract public function __unserialize( array $serialized ): void;

}
