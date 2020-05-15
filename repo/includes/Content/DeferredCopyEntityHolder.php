<?php

namespace Wikibase\Repo\Content;

use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * EntityHolder implementing deferred copying.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DeferredCopyEntityHolder implements EntityHolder {

	/**
	 * @var EntityHolder
	 */
	private $entityHolder;

	/**
	 * @var EntityDocument|null
	 */
	private $entity = null;

	public function __construct( EntityHolder $entityHolder ) {
		$this->entityHolder = $entityHolder;
	}

	/**
	 * @see EntityHolder::getEntityId
	 *
	 * This implements lazy initialization of the entity: when called for the first time,
	 * this method will call getEntity() on the EntityHolder passed to the constructor,
	 * and then deep clone the entity returned. The resulting copy is returned.
	 * Subsequent calls will return the same entity.
	 *
	 * @param string $expectedClass The class with which the result is expected to be compatible.
	 * Defaults to EntityDocument.
	 *
	 * @throws RuntimeException If the entity held by this EntityHolder is not compatible with $expectedClass.
	 * @return EntityDocument
	 */
	public function getEntity( $expectedClass = EntityDocument::class ) {
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
