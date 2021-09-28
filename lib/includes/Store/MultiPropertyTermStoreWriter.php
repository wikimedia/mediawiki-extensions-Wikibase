<?php

namespace Wikibase\Lib\Store;

use Exception;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\DataModel\Term\Fingerprint;
use Wikimedia\Assert\Assert;

/**
 * A {@link PropertyTermStoreWriter} wrapping several other {@link PropertyTermStoreWriter}s.
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
class MultiPropertyTermStoreWriter implements PropertyTermStoreWriter {

	/** @var PropertyTermStoreWriter[] */
	private $propertyTermStoreWriters;

	/**
	 * @param PropertyTermStoreWriter[] $propertyTermStoreWriters
	 */
	public function __construct( array $propertyTermStoreWriters ) {
		Assert::parameterElementType(
			PropertyTermStoreWriter::class,
			$propertyTermStoreWriters,
			'$propertyTermStoreWriters'
		);
		Assert::parameter( $propertyTermStoreWriters !== [], '$propertyTermStoreWriters', 'must not be empty' );
		$this->propertyTermStoreWriters = $propertyTermStoreWriters;
	}

	public function storeTerms( NumericPropertyId $propertyId, Fingerprint $terms ) {
		$firstException = null;
		foreach ( $this->propertyTermStoreWriters as $propertyTermStoreWriter ) {
			try {
				$propertyTermStoreWriter->storeTerms( $propertyId, $terms );
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

	public function deleteTerms( NumericPropertyId $propertyId ) {
		$firstException = null;
		foreach ( $this->propertyTermStoreWriters as $propertyTermStoreWriter ) {
			try {
				$propertyTermStoreWriter->deleteTerms( $propertyId );
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
