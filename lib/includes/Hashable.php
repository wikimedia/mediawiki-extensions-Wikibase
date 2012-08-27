<?php

namespace Wikibase;

/**
 * Interface for objects with a getHash method.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Hashable {

	/**
	 * Returns a hash of the object.
	 * This hash is based on the objects value and identifiers, but not on the instance.
	 * In other words, calling getHash on a clone of an unmodified object will always
	 * result in the same hash.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash();

}
