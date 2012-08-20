<?php

namespace Wikibase;

/**
 * Interface for objects that can hash a map (ie associative array).
 * Elements must implement Hashable.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface MapHasher {

	/**
	 * Computes and returns the hash of the provided map.
	 *
	 * @since 0.1
	 *
	 * @param $map array of Hashable
	 *
	 * @return string
	 */
	public function hash( array $map );

}
