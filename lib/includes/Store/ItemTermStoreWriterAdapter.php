<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Services\Term\TermStoreException;

/**
 * Adapter turning an ItemTermStoreWriter into an EntityTermStoreWriter.
 *
 * @license GPL-2.0-or-later
 */
class ItemTermStoreWriterAdapter implements EntityTermStoreWriter {

	/** @var ItemTermStoreWriter */
	private $store;

	public function __construct( ItemTermStoreWriter $store ) {
		$this->store = $store;
	}

	public function saveTermsOfEntity( EntityDocument $entity ): bool {
		if ( $entity instanceof Item ) {
			try {
				$this->store->storeTerms( $entity->getId(), $entity->getFingerprint() );
				return true;
			} catch ( TermStoreException $ex ) {
				return false;
			}
		}

		throw new InvalidArgumentException( 'Unsupported entity type' );
	}

	public function deleteTermsOfEntity( EntityId $entityId ): bool {
		if ( $entityId instanceof ItemId ) {
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
