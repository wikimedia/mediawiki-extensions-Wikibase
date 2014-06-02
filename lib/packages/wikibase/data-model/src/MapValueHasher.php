<?php

namespace Wikibase\DataModel;

use Hashable;
use InvalidArgumentException;
use Traversable;

/**
 * Generates hashes for associative arrays based on the values of their elements.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MapValueHasher implements MapHasher {

	private $isOrdered;

	public function __construct( $holdOrderIntoAccount = false ) {
		$this->isOrdered = $holdOrderIntoAccount;
	}

	/**
	 * @see MapHasher::hash
	 *
	 * @since 0.1
	 *
	 * @param Traversable|Hashable[] $map
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function hash( $map ) {
		if ( !is_array( $map ) && !( $map instanceof Traversable ) ) {
			throw new InvalidArgumentException( 'MapHasher::hash only accepts Traversable objects (including arrays)' );
		}

		$hashes = array();

		/**
		 * @var Hashable $hashable
		 */
		foreach ( $map as $hashable ) {
			$hashes[] = $hashable->getHash();
		}

		if ( !$this->isOrdered ) {
			sort( $hashes );
		}

		return sha1( implode( '|', $hashes ) );
	}

}
