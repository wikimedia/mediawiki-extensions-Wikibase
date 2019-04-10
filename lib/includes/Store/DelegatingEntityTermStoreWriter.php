<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\TermStore\ItemTermStore;
use Wikibase\TermStore\PropertyTermStore;
use Wikibase\TermStore\TermStoreException;

/**
 * @license GPL-2.0-or-later
 */
class DelegatingEntityTermStoreWriter implements EntityTermStoreWriter {

	private $propertyStore;
	private $itemStore;

	public function __construct( PropertyTermStore $propertyStore, ItemTermStore $itemStore ) {
		$this->propertyStore = $propertyStore;
		$this->itemStore = $itemStore;
	}

	public function saveTermsOfEntity( EntityDocument $entity ) {
		if ( $entity instanceof Item ) {
			return $this->storeItem( $entity );
		}

		if ( $entity instanceof Property ) {
			return $this->storeProperty( $entity );
		}

		throw new \InvalidArgumentException( 'Unsupported entity type' );
	}

	private function storeItem( Item $item ) {
		try {
			$this->itemStore->storeTerms( $item->getId(), $item->getFingerprint() );
			return true;
		} catch ( TermStoreException $ex ) {
			return false;
		}
	}

	private function storeProperty( Property $property ) {
		try {
			$this->propertyStore->storeTerms( $property->getId(), $property->getFingerprint() );
			return true;
		} catch ( TermStoreException $ex ) {
			return false;
		}
	}

	public function deleteTermsOfEntity( EntityId $entityId ) {
		if ( $entityId instanceof ItemId ) {
			return $this->deleteItem( $entityId );
		}

		if ( $entityId instanceof PropertyId ) {
			return $this->deleteProperty( $entityId );
		}

		throw new \InvalidArgumentException( 'Unsupported entity type' );
	}

	private function deleteItem( ItemId $id ) {
		try {
			$this->itemStore->deleteTerms( $id );
			return true;
		} catch ( TermStoreException $ex ) {
			return false;
		}
	}

	private function deleteProperty( PropertyId $id ) {
		try {
			$this->propertyStore->deleteTerms( $id );
			return true;
		} catch ( TermStoreException $ex ) {
			return false;
		}
	}

}
