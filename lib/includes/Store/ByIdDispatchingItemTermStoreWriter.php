<?php

namespace Wikibase\Lib\Store;

use AssertionError;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\Store\Sql\Terms\NullItemTermStoreWriter;
use Wikimedia\Assert\Assert;

/**
 * An {@link ItemTermStoreWriter} wrapping several other {@link ItemTermStoreWriter}s,
 * dispatching to them based on the numeric item ID:
 * each range is assigned one store that’s responsible writing.
 *
 * This class was originally intended to dispatch between both new and old stores
 * but one instance of this class is now used for each new and old stores.
 * As a result only the ID restricting logic is now used.
 *
 * @license GPL-2.0-or-later
 */
class ByIdDispatchingItemTermStoreWriter implements ItemTermStoreWriter {

	/** @var ItemTermStoreWriter[] */
	private $itemTermStoreWriters;

	/** @var bool Should this store error if given an ItemId that it can not store? */
	private $errorIfNoStoreForId;

	/**
	 * @param ItemTermStoreWriter[] $itemTermStoreWriters
	 * Map from maximum item ID number to ItemTermStoreWriter for those IDs.
	 * The dispatcher iterates over this array,
	 * dispatching to the first store whose key is greater than
	 * or equal to the numeric item ID for which the store is called.
	 * It orders the key entries so the lowest one wins first.
	 * At least one of the keys must cover all possible IDs.
	 * Example:
	 *
	 * [ 1000000 => $newStore, 2000000 => $mixedStore, Int32EntityId::MAX => $oldStore ]
	 */
	public function __construct( array $itemTermStoreWriters, $errorIfNoStoreForId = true ) {
		Assert::parameterElementType(
			ItemTermStoreWriter::class,
			$itemTermStoreWriters,
			'$itemTermStoreWriters'
		);
		Assert::parameter( $itemTermStoreWriters !== [], '$itemTermStoreWriters', 'must not be empty' );
		$this->itemTermStoreWriters = $itemTermStoreWriters;
		$this->errorIfNoStoreForId = $errorIfNoStoreForId;
		ksort( $this->itemTermStoreWriters );
	}

	private function getItemTermStoreWriter( ItemId $itemId ): ItemTermStoreWriter {
		foreach ( $this->itemTermStoreWriters as $maxId => $itemTermStore ) {
			if ( $itemId->getNumericId() <= $maxId ) {
				return $itemTermStore;
			}
		}
		if ( $this->errorIfNoStoreForId ) {
			throw new AssertionError(
				'Item ID ' . $itemId->getSerialization() . ' not accepted by any ItemTermStoreWriter'
			);
		}
		return new NullItemTermStoreWriter();
	}

	public function storeTerms( ItemId $itemId, Fingerprint $terms ) {
		$this->getItemTermStoreWriter( $itemId )->storeTerms( $itemId, $terms );
	}

	public function deleteTerms( ItemId $itemId ) {
		$this->getItemTermStoreWriter( $itemId )->deleteTerms( $itemId );
	}

}
