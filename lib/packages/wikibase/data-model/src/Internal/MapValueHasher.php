<?php

namespace Wikibase\DataModel\Internal;

use InvalidArgumentException;
use Traversable;

/**
 * Generates hashes for associative arrays based on the values of their elements.
 *
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MapValueHasher {

	private $isOrdered;

	public function __construct( $holdOrderIntoAccount = false ) {
		$this->isOrdered = $holdOrderIntoAccount;
	}

	/**
	 * Computes and returns the hash of the provided map.
	 *
	 * @since 0.1
	 *
	 * @param Traversable $map
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function hash( $map ) {
		if ( !is_array( $map ) && !( $map instanceof Traversable ) ) {
			throw new InvalidArgumentException( '$map must be an array or an instance of Traversable' );
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
