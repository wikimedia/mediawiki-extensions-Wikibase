<?php

namespace Wikibase;

use AssertionError;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\TermStore\ItemTermStore;
use Wikimedia\Assert\Assert;

/**
 * An {@link ItemTermStore} wrapping several other {@link ItemTermStore}s,
 * dispatching between them based on the numeric item ID:
 * each range is assigned one store that’s responsible for reading and writing.
 *
 * @license GPL-2.0-or-later
 */
class ByIdDispatchingItemTermStore implements ItemTermStore {

	/** @var ItemTermStore[] */
	private $itemTermStores;

	/**
	 * @param ItemTermStore[] $itemTermStores
	 * Map from maximum item ID number to ItemTermStore for those IDs.
	 * The dispatcher iterates over this array,
	 * dispatching to the first store whose key is greater than
	 * or equal to the numeric item ID for which the store is called.
	 * It follows that entries should be in ascending order by key,
	 * and the final one should cover all possible IDs.
	 * Example:
	 *
	 * [ 1000000 => $newStore, 2000000 => $mixedStore, Int32EntityId::MAX => $oldStore ]
	 */
	public function __construct( array $itemTermStores ) {
		Assert::parameterElementType(
			ItemTermStore::class,
			$itemTermStores,
			'$itemTermStores'
		);
		Assert::parameter( $itemTermStores !== [], '$itemTermStores', 'must not be empty' );
		$this->itemTermStores = $itemTermStores;
	}

	private function getItemTermStore( ItemId $itemId ): ItemTermStore {
		foreach ( $this->itemTermStores as $maxId => $itemTermStore ) {
			if ( $itemId->getNumericId() <= $maxId ) {
				return $itemTermStore;
			}
		}
		throw new AssertionError(
			'Item ID ' . $itemId->getSerialization() . ' not accepted by any ItemTermStore'
		);
	}

	public function storeTerms( ItemId $itemId, Fingerprint $terms ) {
		$this->getItemTermStore( $itemId )->storeTerms( $itemId, $terms );
	}

	public function deleteTerms( ItemId $itemId ) {
		$this->getItemTermStore( $itemId )->deleteTerms( $itemId );
	}

	public function getTerms( ItemId $itemId ): Fingerprint {
		return $this->getItemTermStore( $itemId )->getTerms( $itemId );
	}

}
