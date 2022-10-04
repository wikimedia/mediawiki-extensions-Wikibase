<?php

declare( strict_types=1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * PropertyTermStoreWriter implementation for the 2019 SQL based secondary property term storage.
 *
 * This can only be used to write to Property term stores on the local database.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
class DatabasePropertyTermStoreWriter extends DatabaseTermStoreWriterBase implements PropertyTermStoreWriter {

	public function storeTerms( NumericPropertyId $propertyId, Fingerprint $fingerprint ) {
		$this->incrementForQuery( 'PropertyTermStore_storeTerms' );
		$this->store( $propertyId, $fingerprint );
	}

	public function deleteTerms( NumericPropertyId $propertyId ) {
		$this->incrementForQuery( 'PropertyTermStore_deleteTerms' );
		$this->delete( $propertyId );
	}

	protected function makeMapping(): NormalizedTermStorageMapping {
		return NormalizedTermStorageMapping::factory( Property::ENTITY_TYPE );
	}
}
