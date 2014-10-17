<?php

namespace Wikibase\Content;

use RuntimeException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Trivial EntityHolder holding an Entity object.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityInstanceHolder implements EntityHolder {

	/**
	 * @var Entity
	 */
	private $entity;

	/**
	 * @param Entity $entity
	 */
	public function __construct( Entity $entity ) {
		$this->entity = $entity;
	}

	/**
	 * @see EntityHolder::getEntityId
	 *
	 * @param string $expectedClass The class the result is expected to be compatible with.
	 * Defaults to Entity.
	 *
	 * @throws RuntimeException If the entity held by this EntityHolder is not compatible with $expectedClass.
	 * @return Entity
	 */
	public function getEntity( $expectedClass = 'Wikibase\DataModel\Entity\Entity' ) {
		if ( !( $this->entity instanceof $expectedClass ) ) {
			throw new RuntimeException( 'Contained entity is not compatible with ' . $expectedClass );
		}

		return $this->entity;
	}

	/**
	 * @see EntityHolder::getEntityId
	 *
	 * @return EntityId|null
	 */
	public function getEntityId() {
		return $this->entity->getId();
	}

	/**
	 * @see EntityHolder::getEntityType
	 *
	 * @return string
	 */
	public function getEntityType() {
		return $this->entity->getType();
	}

}
 