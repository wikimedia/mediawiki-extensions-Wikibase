<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;

/**
 * Represents a redirect from one EntityId to another.
 *
 * @since 4.2
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityRedirect {

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
			throw new InvalidArgumentException(
				'$entityId (' . $entityId . ') and $targetId (' . $targetId . ') must refer to the same kind of entity.'
			);
		}

		if ( $entityId->getSerialization() === $targetId->getSerialization() ) {
			throw new InvalidArgumentException( '$entityId (' . $entityId . ') and $targetId can not be the same.' );
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
	 * @param mixed $that
	 *
	 * @return bool
	 */
	public function equals( $that ) {
		if ( $that === $this ) {
			return true;
		}

		return is_object( $that )
			&& get_class( $that ) === get_called_class()
			&& $this->entityId->equals( $that->entityId )
			&& $this->targetId->equals( $that->targetId );
	}

	/**
	 * @since 4.4
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->entityId . '->' . $this->targetId;
	}

}
