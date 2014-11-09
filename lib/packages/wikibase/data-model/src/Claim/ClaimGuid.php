<?php

namespace Wikibase\DataModel\Claim;

use Comparable;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ClaimGuid implements Comparable {

	/**
	 * The separator for the prefix and suffix of the GUID.
	 */
	const SEPARATOR = '$';

	private $entityId;
	private $serialization;

	/**
	 * @param EntityId $entityId
	 * @param string $guid
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $entityId, $guid ) {
		if( !$entityId instanceof EntityId ){
			throw new InvalidArgumentException( '$entityId must be an instance of EntityId' );
		}
		if( !is_string( $guid ) ){
			throw new InvalidArgumentException( '$guid must be a string; got ' . gettype( $guid ) );
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
	 * @param ClaimGuid $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		return $target instanceof self
			&& $target->serialization === $this->serialization;
	}

	public function __toString() {
		return $this->serialization;
	}

}
