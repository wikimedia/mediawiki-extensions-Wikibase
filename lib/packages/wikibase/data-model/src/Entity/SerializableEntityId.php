<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;

/**
 *
 * @license GPL-2.0-or-later
 */
abstract class SerializableEntityId implements EntityId {

	protected $serialization;

	/**
	 * @var string
	 */
	protected $repositoryName;

	/**
	 * @var string
	 */
	protected $localPart;

	public const PATTERN = '/^:?(\w+:)*[^:]+\z/';

	/**
	 * @param string $serialization
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $serialization ) {
		self::assertValidSerialization( $serialization );
		$this->serialization = self::normalizeIdSerialization( $serialization );

		list( $this->repositoryName, $this->localPart ) = self::extractRepositoryNameAndLocalPart( $serialization );
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
	 * Returns an array with 3 elements: the foreign repository name as the first element, the local ID as the last
	 * element and everything that is in between as the second element.
	 *
	 * SerializableEntityId::joinSerialization can be used to restore the original serialization from the parts returned.
	 *
	 * @param string $serialization
	 *
	 * @throws InvalidArgumentException
	 * @return string[] Array containing the serialization split into 3 parts.
	 */
	public static function splitSerialization( $serialization ) {
		self::assertValidSerialization( $serialization );

		return self::extractSerializationParts( self::normalizeIdSerialization( $serialization ) );
	}

	/**
	 * Splits the given ID serialization into an array with the following three elements:
	 *  - the repository name as the first element (empty string for local repository)
	 *  - any parts of the ID serialization but the repository name and the local ID (if any, empty string
	 *    if nothing else present)
	 *  - the local ID
	 * Note: this method does not perform any validation of the given input. Calling code should take
	 * care of this!
	 *
	 * @param string $serialization
	 *
	 * @return string[]
	 */
	private static function extractSerializationParts( $serialization ) {
		$parts = explode( ':', $serialization );
		$localPart = array_pop( $parts );
		$repoName = array_shift( $parts );
		$prefixRemainder = implode( ':', $parts );

		return [
			is_string( $repoName ) ? $repoName : '',
			$prefixRemainder,
			$localPart,
		];
	}

	/**
	 * Builds an ID serialization from the parts returned by SerializableEntityId::splitSerialization.
	 *
	 * @param string[] $parts
	 *
	 * @throws InvalidArgumentException
	 * @return string
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
	 * @return string
	 */
	public function getRepositoryName() {
		return $this->repositoryName;
	}

	/**
	 * Returns the serialization without the first repository prefix.
	 *
	 * @return string
	 */
	public function getLocalPart() {
		return $this->localPart;
	}

	/**
	 * Returns true iff SerializableEntityId::getRepositoryName returns a non-empty string.
	 *
	 * @return bool
	 */
	public function isForeign() {
		return $this->repositoryName !== '';
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

	/**
	 * Returns a pair (repository name, local part of ID) from the given ID serialization.
	 * Note: this does not perform any validation of the given input. Calling code should take
	 * care of this!
	 *
	 * @param string $serialization
	 *
	 * @return string[] Array of form [ string $repositoryName, string $localPart ]
	 */
	protected static function extractRepositoryNameAndLocalPart( $serialization ) {
		return array_pad( explode( ':', $serialization, 2 ), -2, '' );
	}

}
