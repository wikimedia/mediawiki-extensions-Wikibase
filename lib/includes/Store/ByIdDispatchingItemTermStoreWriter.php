<?php

namespace Wikibase\Lib\Store;

use AssertionError;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Term\Fingerprint;
use Wikimedia\Assert\Assert;

/**
 * An {@link ItemTermStoreWriter} wrapping several other {@link ItemTermStoreWriter}s,
 * dispatching between them based on the numeric item ID:
 * each range is assigned one store that’s responsible writing.
 *
 * @license GPL-2.0-or-later
 */
class ByIdDispatchingItemTermStoreWriter implements ItemTermStoreWriter {

	/** @var ItemTermStoreWriter[] */
	private $itemTermStoreWriters;

	/**
	 * @param ItemTermStoreWriter[] $itemTermStoreWriters
	 * Map from maximum item ID number to ItemTermStore for those IDs.
	 * The dispatcher iterates over this array,
	 * dispatching to the first store whose key is greater than
	 * or equal to the numeric item ID for which the store is called.
	 * It orders the key entries so the lowest one wins first.
	 * At least one of the keys must cover all possible IDs.
	 * Example:
	 *
	 * [ 1000000 => $newStore, 2000000 => $mixedStore, Int32EntityId::MAX => $oldStore ]
	 */
	public function __construct( array $itemTermStoreWriters ) {
		Assert::parameterElementType(
			ItemTermStoreWriter::class,
			$itemTermStoreWriters,
			'$itemTermStoreWriters'
		);
		Assert::parameter( $itemTermStoreWriters !== [], '$itemTermStoreWriters', 'must not be empty' );
		$this->itemTermStoreWriters = $itemTermStoreWriters;
		ksort( $this->itemTermStoreWriters );
	}

	private function getItemTermStoreWriter( ItemId $itemId ): ItemTermStoreWriter {
		foreach ( $this->itemTermStoreWriters as $maxId => $itemTermStoreWriter ) {
			if ( $itemId->getNumericId() <= $maxId ) {
				return $itemTermStoreWriter;
			}
		}
		throw new AssertionError(
			'Item ID ' . $itemId->getSerialization() . ' not accepted by any ItemTermStoreWriter'
		);
	}

	public function storeTerms( ItemId $itemId, Fingerprint $terms ) {
		$this->getItemTermStoreWriter( $itemId )->storeTerms( $itemId, $terms );
	}

	public function deleteTerms( ItemId $itemId ) {
		$this->getItemTermStoreWriter( $itemId )->deleteTerms( $itemId );
	}

}
