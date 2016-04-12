<?php

namespace Wikibase\DataModel\Statement;

use Comparable;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Immutable value object for a statement id. A statement id consists of the entity id serialization
 * of the entity it belongs to (e.g. "Q1") and a randomly generated global unique identifier (GUID),
 * separated by a dollar sign.
 *
 * @since 3.0
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class StatementGuid implements Comparable {

	/**
	 * The separator for the prefix and suffix of the GUID.
	 */
	const SEPARATOR = '$';

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @var string
	 */
	private $serialization;

	/**
	 * @param EntityId $entityId
	 * @param string $guid
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $entityId, $guid ) {
		if ( !( $entityId instanceof EntityId ) ) {
			throw new InvalidArgumentException( '$entityId must be an instance of EntityId' );
		}
		if ( !is_string( $guid ) ) {
			throw new InvalidArgumentException( '$guid must be a string' );
		}

		$this->serialization = $entityId->getSerialization() . self::SEPARATOR . $guid;
		$this->entityId = $entityId;
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * @return string
	 */
	public function getSerialization() {
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

	public function __toString() {
		return $this->serialization;
	}

}
