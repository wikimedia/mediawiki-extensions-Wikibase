<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\DataModel\Services\Term\TermStoreException;

/**
 * Adapter turning a PropertyTermStoreWriter into an EntityTermStoreWriter.
 *
 * @license GPL-2.0-or-later
 */
class PropertyTermStoreWriterAdapter implements EntityTermStoreWriter {

	/** @var PropertyTermStoreWriter */
	private $store;

	public function __construct( PropertyTermStoreWriter $store ) {
		$this->store = $store;
	}

	public function saveTermsOfEntity( EntityDocument $entity ): bool {
		$id = $entity->getId();

		if ( $entity instanceof Property && $id instanceof NumericPropertyId ) {
			try {
				$this->store->storeTerms( $id, $entity->getFingerprint() );
				return true;
			} catch ( TermStoreException $ex ) {
				return false;
			}
		}

		throw new InvalidArgumentException( 'Unsupported entity type' );
	}

	public function deleteTermsOfEntity( EntityId $entityId ): bool {
		if ( $entityId instanceof NumericPropertyId ) {
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
