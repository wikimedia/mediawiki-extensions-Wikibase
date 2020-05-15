<?php

namespace Wikibase\Repo\Content;

use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Trivial EntityHolder holding an entity object.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityInstanceHolder implements EntityHolder {

	/**
	 * @var EntityDocument
	 */
	private $entity;

	public function __construct( EntityDocument $entity ) {
		$this->entity = $entity;
	}

	/**
	 * @see EntityHolder::getEntityId
	 *
	 * @param string $expectedClass The class the result is expected to be compatible with.
	 * Defaults to EntityDocument.
	 *
	 * @throws RuntimeException If the entity held by this EntityHolder is not compatible with $expectedClass.
	 * @return EntityDocument
	 */
	public function getEntity( $expectedClass = EntityDocument::class ) {
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
