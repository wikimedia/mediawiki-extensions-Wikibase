<?php

namespace Wikibase\DataModel\Statement;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Immutable value object for a statement id. A statement id consists of the entity id serialization
 * of the entity it belongs to (e.g. "Q1") and a randomly generated global unique identifier (GUID),
 * separated by a dollar sign.
 *
 * @since 3.0
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class StatementGuid {

	/**
	 * The separator for the prefix and suffix of the GUID.
	 */
	public const SEPARATOR = '$';

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @var string
	 */
	private $guidPart;

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
	public function __construct( EntityId $entityId, $guid ) {
		if ( !is_string( $guid ) ) {
			throw new InvalidArgumentException( '$guid must be a string' );
		}

		$this->serialization = $entityId->getSerialization() . self::SEPARATOR . $guid;
		$this->entityId = $entityId;
		$this->guidPart = $guid;
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * @since 9.4
	 *
	 * @return string
	 */
	public function getGuidPart() {
		return $this->guidPart;
	}

	/**
	 * @return string
	 * @deprecated The value returned by this method might differ in case from the original, unparsed statement GUID
	 * (the entity ID part might have been lowercase originally, but is always normalized in the return value here),
	 * which means that the value should not be compared to other statement GUID serializations,
	 * e.g. to look up a statement in a StatementList.
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
