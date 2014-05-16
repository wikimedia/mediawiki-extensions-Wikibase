<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Represents a redirect from one EntityId to another.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityRedirect  {

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @var EntityId
	 */
	private $targetId;

	/**
	 * @param EntityId $entityId
	 * @param EntityId $targetId
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityId $entityId, EntityId $targetId ) {
		if ( $entityId->getEntityType() !== $targetId->getEntityType() ) {
			throw new \InvalidArgumentException( '$entityId and $targetId must refer to the same kind of entity' );
		}

		$this->entityId = $entityId;
		$this->targetId = $targetId;
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * @return EntityId
	 */
	public function getTargetId() {
		return $this->targetId;
	}

	/**
	 * @param EntityRedirect $that
	 *
	 * @return bool
	 */
	public function equals( $that ) {
		return is_object( $that )
			&& get_class( $that ) === get_class( $this )
			&& $this->entityId->equals( $that->entityId )
			&& $this->targetId->equals( $that->targetId );
	}

}
