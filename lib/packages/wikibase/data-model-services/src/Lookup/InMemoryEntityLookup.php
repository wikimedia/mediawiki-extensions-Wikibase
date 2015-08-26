<?php

namespace Wikibase\DataModel\Services\Lookup;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * EntityLookup that uses an in memory array to retrieve the requested information.
 * This class can be used as a fake in tests.
 *
 * @since 2.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class InMemoryEntityLookup implements EntityLookup {

	private $entities = array();

	/**
	 * @param EntityDocument $entity
	 *
	 * @throws InvalidArgumentException
	 */
	public function addEntity( EntityDocument $entity ) {
		if ( $entity->getId() === null ) {
			throw new InvalidArgumentException( 'The entity needs to have an ID' );
		}

		$this->entities[$entity->getId()->getSerialization()] = $entity;
	}

	/**
	 * @see EntityLookup::getEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @throws EntityLookupException
	 * @return EntityDocument
	 */
	public function getEntity( EntityId $entityId ) {
		if ( array_key_exists( $entityId->getSerialization(), $this->entities ) ) {
			return $this->entities[$entityId->getSerialization()];
		}

		return null;
	}

	/**
	 * @see EntityLookup::hasEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		return array_key_exists( $entityId->getSerialization(), $this->entities );
	}

}
