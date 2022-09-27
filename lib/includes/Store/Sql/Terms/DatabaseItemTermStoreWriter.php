<?php

declare( strict_types=1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * ItemTermStoreWriter implementation for the 2019 SQL based secondary item term storage.
 *
 * This can only be used to write to Item term stores on the local database.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
class DatabaseItemTermStoreWriter extends DatabaseTermStoreWriterBase implements ItemTermStoreWriter {

	public function storeTerms( ItemId $itemId, Fingerprint $fingerprint ) {
		$this->incrementForQuery( 'ItemTermStore_storeTerms' );
		$this->store( $itemId, $fingerprint );
	}

	public function deleteTerms( ItemId $itemId ) {
		$this->incrementForQuery( 'ItemTermStore_deleteTerms' );
		$this->delete( $itemId );
	}

	protected function makeMapping(): NormalizedTermStorageMapping {
		return NormalizedTermStorageMapping::factory( Item::ENTITY_TYPE );
	}
}
