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

	/**
	 * @since 0.1
	 *
	 * @var boolean
	 */
	protected $ordered;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param boolean $ordered
	 */
	public function __construct( $ordered = false ) {
		$this->ordered = $ordered;
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

		if ( !$this->ordered ) {
			sort( $hashes );
		}

		return sha1( implode( '|', $hashes ) );
	}

}
