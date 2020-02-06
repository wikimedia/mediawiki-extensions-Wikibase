<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * Null implementation only really intended to be used with write related methods.
 *
 * Primary use is in ByIdDispatchingItemTermStore.
 *
 * @license GPL-2.0-or-later
 */
class NullItemTermStoreWriter implements ItemTermStoreWriter {

	/**
	 * @inheritDoc
	 */
	public function storeTerms( ItemId $itemId, Fingerprint $terms ) {
	}

	/**
	 * @inheritDoc
	 */
	public function deleteTerms( ItemId $itemId ) {
	}

}
