<?php

namespace Wikibase\DataModel\Internal;

use InvalidArgumentException;
use Traversable;
use Wikibase\DataModel\Reference;

/**
 * Generates hashes for associative arrays based on the values of their elements.
 *
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MapValueHasher {

	private bool $isOrdered;

	public function __construct( $holdOrderIntoAccount = false ) {
		$this->isOrdered = $holdOrderIntoAccount;
	}

	/**
	 * Computes and returns the hash of the provided map.
	 *
	 * @since 0.1
	 *
	 * @param Traversable|Reference[] $map
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function hash( $map ) {
		if ( !is_iterable( $map ) ) {
			throw new InvalidArgumentException( '$map must be a Reference array or an instance of Traversable' );
		}

		$hashes = [];

		foreach ( $map as $hashable ) {
			$hashes[] = $hashable->getHash();
		}

		if ( !$this->isOrdered ) {
			sort( $hashes );
		}

		return sha1( implode( '|', $hashes ) );
	}

}
