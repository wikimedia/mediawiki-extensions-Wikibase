<?php

namespace Wikibase\DataModel\Entity;

use Comparable;
use InvalidArgumentException;
use Serializable;

/**
 * @since 0.5
 * Abstract since 2.0
 *
 * @license GPL-2.0+
 */
abstract class EntityId implements Comparable, Serializable {

	protected $serialization;

	const PATTERN = '/^:?(\w+:)*[^:]+\z/';

	/**
	 * @since 6.2
	 *
	 * @param string $serialization
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
	public abstract function getEntityType();

	/**
	 * @return string
	 */
	public function getSerialization() {
		return $this->serialization;
	}

	/**
	 * Returns an array with 3 elements: the foreign repository name as the first element, the local ID as the last
	 * element and everything that is in between as the second element.
	 *
	 * EntityId::joinSerialization can be used to restore the original serialization from the parts returned.
	 *
	 * @since 6.2
	 *
	 * @param string $serialization
	 * @return string[] Array containing the serialization split into 3 parts.
	 */
	public static function splitSerialization( $serialization ) {
		self::assertValidSerialization( $serialization );

		$parts = explode( ':', self::normalizeIdSerialization( $serialization ) );
		$localPart = array_pop( $parts );
		$repoName = array_shift( $parts );
		$prefixRemainder = implode( ':', $parts );

		return [
			is_string( $repoName ) ? $repoName : '',
			$prefixRemainder,
			$localPart
		];
	}

	/**
	 * Builds an ID serialization from the parts returned by EntityId::splitSerialization.
	 *
	 * @since 6.2
	 *
	 * @param string[] $parts
	 * @return string
	 *
	 * @throws InvalidArgumentException
	 */
	public static function joinSerialization( array $parts ) {
		if ( end( $parts ) === '' ) {
			throw new InvalidArgumentException( 'The last element of $parts must not be empty.' );
		}

		return implode(
			':',
			array_filter( $parts, function( $part ) {
				return $part !== '';
			} )
		);
	}

	/**
	 * Returns '' for local IDs and the foreign repository name for foreign IDs. For chained IDs (e.g. foo:bar:Q42) it
	 * will return only the first part.
	 *
	 * @since 6.2
	 *
	 * @return string
	 */
	public function getRepositoryName() {
		$parts = self::splitSerialization( $this->serialization );

		return $parts[0];
	}

	/**
	 * Returns the serialization without the first repository prefix.
	 *
	 * @since 6.2
	 *
	 * @return string
	 */
	public function getLocalPart() {
		$parts = self::splitSerialization( $this->serialization );

		return self::joinSerialization( [ '', $parts[1], $parts[2] ] );
	}

	/**
	 * Returns true iff EntityId::getRepoName returns a non-empty string.
	 *
	 * @since 6.2
	 *
	 * @return bool
	 */
	public function isForeign() {
		// not actually using EntityId::getRepoName for performance reasons
		return strpos( $this->serialization, ':' ) > 0;
	}

	/**
	 * @param string $id
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
	 * @see Comparable::equals
	 *
	 * @since 0.5
	 *
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

}
