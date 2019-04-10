<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\TermStore\PropertyTermStore;
use Wikibase\TermStore\TermStoreException;

/**
 * @license GPL-2.0-or-later
 */
class DelegatingEntityTermStoreWriter implements EntityTermStoreWriter {

	private $propertyStore;

	public function __construct( PropertyTermStore $propertyStore ) {
		$this->propertyStore = $propertyStore;
	}

	public function saveTerms( EntityDocument $entity ) {
		if ( $entity instanceof Property ) {
			return $this->storeProperty( $entity );
		}

		throw new \InvalidArgumentException( 'Unsupported entity type' );
	}

	private function storeProperty( Property $property ) {
		try {
			$this->propertyStore->storeTerms( $property->getId(), $property->getFingerprint() );
			return true;
		}
		catch ( TermStoreException $ex ) {
			return false;
		}
	}

	public function deleteTerms( EntityId $entityId ) {
		if ( $entityId instanceof PropertyId ) {
			return $this->deleteProperty( $entityId );
		}

		throw new \InvalidArgumentException( 'Unsupported entity type' );
	}

	private function deleteProperty( PropertyId $id ) {
		try {
			$this->propertyStore->deleteTerms( $id );
			return true;
		}
		catch ( TermStoreException $ex ) {
			return false;
		}
	}

}
