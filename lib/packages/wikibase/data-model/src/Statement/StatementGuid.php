<?php declare( strict_types=1 );

namespace Wikibase\DataModel\Statement;

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

	private EntityId $entityId;
	private string $guidPart;
	private string $serialization;

	public function __construct( EntityId $entityId, string $guid ) {
		$this->serialization = $entityId->getSerialization() . self::SEPARATOR . $guid;
		$this->entityId = $entityId;
		$this->guidPart = $guid;
	}

	public function getEntityId(): EntityId {
		return $this->entityId;
	}

	/**
	 * @since 9.4
	 */
	public function getGuidPart(): string {
		return $this->guidPart;
	}

	/**
	 * @deprecated The value returned by this method might differ in case from the original, unparsed statement GUID
	 * (the entity ID part might have been lowercase originally, but is always normalized in the return value here),
	 * which means that the value should not be compared to other statement GUID serializations,
	 * e.g. to look up a statement in a StatementList.
	 */
	public function getSerialization(): string {
		return $this->serialization;
	}

	/**
	 * @param mixed $target
	 */
	public function equals( $target ): bool {
		if ( $this === $target ) {
			return true;
		}

		return $target instanceof self && $target->serialization === $this->serialization;
	}

	public function __toString(): string {
		return $this->serialization;
	}

}
