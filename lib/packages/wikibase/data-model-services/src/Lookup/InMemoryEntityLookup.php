<?php

namespace Wikibase\DataModel\Services\Lookup;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * EntityLookup that uses an in memory array to retrieve the requested information.
 * One can also specify exceptions that should be thrown when an entity with their
 * associated ID is requested.
 *
 * This class can be used as a fake in tests.
 *
 * @since 2.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class InMemoryEntityLookup implements EntityLookup, ItemLookup, PropertyLookup {

	/**
	 * @var EntityDocument[]
	 */
	private $entities = [];

	/**
	 * @var EntityLookupException[]
	 */
	private $exceptions = [];

	/**
	 * @param EntityDocument ...$entities
	 */
	public function __construct( ...$entities ) {
		foreach ( $entities as $entity ) {
			$this->addEntity( $entity );
		}
	}

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
	 * Registers an exception that will be thrown when a entity with the id in the exception is requested.
	 * If an exception with the same EntityId was already present it will be replaced by the new one.
	 *
	 * @since 3.1
	 *
	 * @param EntityLookupException $exception
	 */
	public function addException( EntityLookupException $exception ) {
		$this->exceptions[$exception->getEntityId()->getSerialization()] = $exception;
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
		$this->throwExceptionIfNeeded( $entityId );

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
	 * @throws EntityLookupException
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		$this->throwExceptionIfNeeded( $entityId );

		return array_key_exists( $entityId->getSerialization(), $this->entities );
	}

	private function throwExceptionIfNeeded( EntityId $entityId ) {
		if ( array_key_exists( $entityId->getSerialization(), $this->exceptions ) ) {
			throw $this->exceptions[$entityId->getSerialization()];
		}
	}

	public function getItemForId( ItemId $itemId ) {
		return ( new LegacyAdapterItemLookup( $this ) )->getItemForId( $itemId );
	}

	public function getPropertyForId( PropertyId $propertyId ) {
		return ( new LegacyAdapterPropertyLookup( $this ) )->getpropertyForId( $propertyId );
	}

}
