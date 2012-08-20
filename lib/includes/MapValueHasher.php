<?php

namespace Wikibase;

/**
 * Generates hashes for associative arrays based on the values of their elements.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
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
	 * @param $map array of Hashable
	 *
	 * @return string
	 */
	public function hash( array $map ) {
		$hashes = array_map(
			function( Hashable $element ) {
				return $element->getHash();
			},
			$map
		);

		if ( !$this->ordered ) {
			sort( $hashes );
		}

		return md5( implode( '|', $hashes ) );
	}

}
