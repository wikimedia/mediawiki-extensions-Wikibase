<?php

namespace Wikibase\DataModel\Claim;

use Comparable;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ClaimGuid implements Comparable {

	/**
	 * The separator for the prefix and suffix of the guid
	 */
	const SEPARATOR = '$';

	/**
	 * @var EntityId
	 */
	protected $entityId;

	/**
	 * @var string
	 */
	protected $serialization;

	/**
	 * @param EntityId $entityId
	 * @param string $guid
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $entityId, $guid ) {

		if( !$entityId instanceof EntityId ){
			throw new \InvalidArgumentException( '$entityId needs to be an EntityId' );
		}
		if( !is_string( $guid ) ){
			throw new \InvalidArgumentException( '$guid needs to be a string' );
		}

		$this->serialization = $entityId->getPrefixedId() . self::SEPARATOR . $guid;
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
	 * @return bool
	 */
	public function equals( $target ) {
		return $target instanceof self
			&& $target->__toString() === $this->__toString();
	}

	public function __toString() {
		return $this->getSerialization();
	}

}