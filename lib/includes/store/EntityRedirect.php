<?php

namespace Wikibase\Lib\Store;

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
	 */
	function __construct( EntityId $entityId, EntityId $targetId ) {
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
	 * @param EntityId $that
	 *
	 * @return bool
	 */
	public function equals( $that ) {
		if ( !is_object( $that )) {
			return false;
		} elseif ( get_class( $that ) !== get_class( $this ) ) {
			return false;
		} elseif ( !$this->getEntityId()->equals( $that->getEntityId() ) ) {
			return false;
		} elseif ( !$this->getTargetId()->equals( $that->getTargetId() ) ) {
			return false;
		} else {
			return true;
		}
	}

}
