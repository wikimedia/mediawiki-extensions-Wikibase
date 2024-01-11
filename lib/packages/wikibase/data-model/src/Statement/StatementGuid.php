<?php declare( strict_types=1 );

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

	private EntityId $entityId;
	private string $guidPart;
	private string $serialization;

	/**
	 * @param EntityId $entityId the normalized entity id
	 * @param string $guidPart the guid part of the statement id that appears after the separator
	 * @param string|null $originalStatementId the original, non-normalized, statement id.
	 * Defaults to `null` for compatability.
	 */
	public function __construct( EntityId $entityId, string $guidPart, string $originalStatementId = null ) {
		$constructedStatementId = $entityId->getSerialization() . self::SEPARATOR . $guidPart;
		if ( $originalStatementId !== null
			 && strtolower( $originalStatementId ) !== strtolower( $constructedStatementId ) ) {
			throw new InvalidArgumentException( '$originalStatementId does not match $entityId and/or $guidPart' );
		}

		// use the original serialization when available to avoid normalizing the entity id prefix
		$this->serialization = $originalStatementId ?? $constructedStatementId;
		$this->entityId = $entityId;
		$this->guidPart = $guidPart;
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
	 * If the `$originalStatementId` parameter is not used when constructing the StatementGuid object,
	 * then this method will return a statement id where the entity id prefix is normalized to upper case.
	 * This could cause issues when comparing to other statement id serializations,
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
