<?php

namespace Wikibase\Content;

use RuntimeException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;

/**
 * EntityHolder implementing deferred copying.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class DeferredCopyEntityHolder implements EntityHolder {

	/**
	 * @var EntityHolder
	 */
	private $entityHolder;

	/**
	 * @var Entity
	 */
	private $entity = null;

	/**
	 * @param EntityHolder $entityHolder
	 */
	public function __construct( EntityHolder $entityHolder ) {
		$this->entityHolder = $entityHolder;
	}

	/**
	 * @see EntityHolder::getEntityId
	 *
	 * This implements lazy initialization of the entity: when called for the first time,
	 * this method will call getEntity() on the EntityHolder passed to the constructor,
	 * and then calls copy() on the entity returned. The resulting copy is returned.
	 * Subsequent calls will return the same entity.
	 *
	 * @param string $expectedClass The class the result is expected to be compatible with.
	 * Defaults to Entity.
	 *
	 * @throws RuntimeException If the entity held by this EntityHolder is not compatible with $expectedClass.
	 * @return Entity
	 */
	public function getEntity( $expectedClass = 'Wikibase\DataModel\Entity\Entity' ) {
		if ( !$this->entity ) {
			$entity = $this->entityHolder->getEntity( $expectedClass );
			$this->entity = $entity->copy();
		}

		return $this->entity;
	}

	/**
	 * @see EntityHolder::getEntityId
	 *
	 * @return EntityId|null
	 */
	public function getEntityId() {
		return $this->entityHolder->getEntityId();
	}

	/**
	 * @see EntityHolder::getEntityType
	 *
	 * @return string
	 */
	public function getEntityType() {
		return $this->entityHolder->getEntityType();
	}

}
 