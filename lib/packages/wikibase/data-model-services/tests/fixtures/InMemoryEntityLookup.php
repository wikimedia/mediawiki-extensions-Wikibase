<?php

namespace Wikibase\DataModel\Services\Fixtures;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;

class InMemoryEntityLookup implements EntityLookup {

	private $entities = array();

	public function addEntity( EntityDocument $entity ) {
		if ( $entity->getId() === null ) {
			throw new \InvalidArgumentException( 'The entity needs to have an ID' );
		}

		$this->entities[$entity->getId()->getSerialization()] = $entity;
	}

	public function getEntity( EntityId $entityId ) {
		if ( array_key_exists( $entityId->getSerialization(), $this->entities ) ) {
			return $this->entities[$entityId->getSerialization()];
		}

		return null;
	}

	public function hasEntity( EntityId $entityId ) {
		return array_key_exists( $entityId->getSerialization(), $this->entities );
	}

}
