<?php

namespace Wikibase;

use Exception;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\TermStore\PropertyTermStore;
use Wikimedia\Assert\Assert;

/**
 * A {@link PropertyTermStore} wrapping several other {@link PropertyTermStore}s.
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
class MultiPropertyTermStore implements PropertyTermStore {

	/** @var PropertyTermStore[] */
	private $propertyTermStores;

	/**
	 * @param PropertyTermStore[] $propertyTermStores
	 */
	public function __construct( array $propertyTermStores ) {
		Assert::parameterElementType(
			PropertyTermStore::class,
			$propertyTermStores,
			'$propertyTermStores'
		);
		Assert::parameter( $propertyTermStores !== [], '$propertyTermStores', 'must not be empty' );
		$this->propertyTermStores = $propertyTermStores;
	}

	public function storeTerms( PropertyId $propertyId, Fingerprint $terms ) {
		$firstException = null;
		foreach ( $this->propertyTermStores as $propertyTermStore ) {
			try {
				$propertyTermStore->storeTerms( $propertyId, $terms );
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

	public function deleteTerms( PropertyId $propertyId ) {
		$firstException = null;
		foreach ( $this->propertyTermStores as $propertyTermStore ) {
			try {
				$propertyTermStore->deleteTerms( $propertyId );
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

	public function getTerms( PropertyId $propertyId ): Fingerprint {
		foreach ( $this->propertyTermStores as $propertyTermStore ) {
			$fingerprint = $propertyTermStore->getTerms( $propertyId );
			if ( !$fingerprint->isEmpty() ) {
				return $fingerprint;
			}
		}
		return new Fingerprint( /* empty */ );
	}

}
