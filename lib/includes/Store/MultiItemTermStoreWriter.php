<?php

namespace Wikibase\Lib\Store;

use Exception;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Term\Fingerprint;
use Wikimedia\Assert\Assert;

/**
 * An {@link ItemTermStoreWriter} wrapping several other {@link ItemTermStoreWriter}s.
 * Writes are multiplexed to all of them.
 *
 * If a write (store or delete terms) throws an exception,
 * writes to the remaining stores are still carried out,
 * and the exception is re-thrown at the end.
 * (If more than one store throws an exception,
 * only the first one is thrown and the other ones will be dropped.)
 *
 * @license GPL-2.0-or-later
 */
class MultiItemTermStoreWriter implements ItemTermStoreWriter {

	/** @var ItemTermStoreWriter[] */
	private $itemTermStoreWriters;

	/**
	 * @param ItemTermStoreWriter[] $itemTermStoreWriters
	 */
	public function __construct( array $itemTermStoreWriters ) {
		Assert::parameterElementType(
			ItemTermStoreWriter::class,
			$itemTermStoreWriters,
			'$itemTermStoreWriters'
		);
		Assert::parameter( $itemTermStoreWriters !== [], '$itemTermStoreWriters', 'must not be empty' );
		$this->itemTermStoreWriters = $itemTermStoreWriters;
	}

	public function storeTerms( ItemId $itemId, Fingerprint $terms ) {
		$firstException = null;
		foreach ( $this->itemTermStoreWriters as $itemTermStoreWriter ) {
			try {
				$itemTermStoreWriter->storeTerms( $itemId, $terms );
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
		foreach ( $this->itemTermStoreWriters as $itemTermStoreWriter ) {
			try {
				$itemTermStoreWriter->deleteTerms( $itemId );
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

}
