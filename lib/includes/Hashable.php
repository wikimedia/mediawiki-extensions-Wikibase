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
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHash();

}
