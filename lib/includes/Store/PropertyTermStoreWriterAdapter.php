<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\DataModel\Services\Term\TermStoreException;

/**
 * Adapter turning a PropertyTermStoreWriter into an EntityTermStoreWriter.
 *
 * @license GPL-2.0-or-later
 */
class PropertyTermStoreWriterAdapter implements EntityTermStoreWriter {

	private $store;

	public function __construct( PropertyTermStoreWriter $store ) {
		$this->store = $store;
	}

	public function saveTermsOfEntity( EntityDocument $entity ) {
		if ( $entity instanceof Property ) {
			try {
				$this->store->storeTerms( $entity->getId(), $entity->getFingerprint() );
				return true;
			} catch ( TermStoreException $ex ) {
				return false;
			}
		}

		throw new InvalidArgumentException( 'Unsupported entity type' );
	}

	public function deleteTermsOfEntity( EntityId $entityId ) {
		if ( $entityId instanceof PropertyId ) {
			try {
				$this->store->deleteTerms( $entityId );
				return true;
			} catch ( TermStoreException $ex ) {
				return false;
			}
		}

		throw new InvalidArgumentException( 'Unsupported entity type' );
	}

}
