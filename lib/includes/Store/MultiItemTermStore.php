<?php

namespace Wikibase;

use Exception;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\TermStore\ItemTermStore;
use Wikimedia\Assert\Assert;

/**
 * An {@link ItemTermStore} wrapping several other {@link ItemTermStore}s.
 * Writes are multiplexed to all of them,
 * reads start with the first one and fall back to the others
 * if an empty {@link Fingerprint} is returned.
 *
 * If a write (store or delete terms) throws an exception,
 * writes to the remaining stores are still carried out,
 * and the exception is re-thrown at the end.
 * (If more than one store throws an exception,
 * only the first one is thrown and the other ones will be dropped.)
 *
 * @license GPL-2.0-or-later
 */
class MultiItemTermStore implements ItemTermStore {

	/** @var ItemTermStore[] */
	private $itemTermStores;

	/**
	 * @param ItemTermStore[] $itemTermStores
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

	public function storeTerms( ItemId $itemId, Fingerprint $terms ) {
		$firstException = null;
		foreach ( $this->itemTermStores as $itemTermStore ) {
			try {
				$itemTermStore->storeTerms( $itemId, $terms );
			} catch ( Exception $exception ) {
				if ( $firstException === null ) {
					$firstException = $exception;
				}
			}
		}
		if ( $firstException !== null ) {
			throw $firstException;
		}
	}

	public function deleteTerms( ItemId $itemId ) {
		$firstException = null;
		foreach ( $this->itemTermStores as $itemTermStore ) {
			try {
				$itemTermStore->deleteTerms( $itemId );
			} catch ( Exception $exception ) {
				if ( $firstException === null ) {
					$firstException = $exception;
				}
			}
		}
		if ( $firstException !== null ) {
			throw $firstException;
		}
	}

	public function getTerms( ItemId $itemId ): Fingerprint {
		foreach ( $this->itemTermStores as $itemTermStore ) {
			$fingerprint = $itemTermStore->getTerms( $itemId );
			if ( !$fingerprint->isEmpty() ) {
				return $fingerprint;
			}
		}
		return new Fingerprint( /* empty */ );
	}

}
