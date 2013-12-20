<?php

namespace Wikibase\DataModel;

use Traversable;

/**
 * Interface for objects that can hash a map (ie associative array).
 * Elements must implement Hashable.
 *
 * TODO: might want to make this internal to DataModel.
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
	 * @param $map Traversable|array of Hashable
	 *
	 * @return string
	 */
	public function hash( $map );

}
