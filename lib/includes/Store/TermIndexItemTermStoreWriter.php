<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * An adapter to turn a {@link TermIndex} into an {@link ItemTermStoreWriter}.
 *
 * @license GPL-2.0-or-later
 */
class TermIndexItemTermStoreWriter implements ItemTermStoreWriter {

	/** @var TermIndex */
	private $termIndex;

	public function __construct( TermIndex $termIndex ) {
		$this->termIndex = $termIndex;
	}

	public function storeTerms( ItemId $itemId, Fingerprint $terms ) {
		$item = new Item( $itemId, $terms );
		$this->termIndex->saveTermsOfEntity( $item );
	}

	public function deleteTerms( ItemId $itemId ) {
		$this->termIndex->deleteTermsOfEntity( $itemId );
	}

}
