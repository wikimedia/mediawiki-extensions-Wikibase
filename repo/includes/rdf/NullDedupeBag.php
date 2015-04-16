<?php

namespace Wikibase;

/**
 * Null implementation of DedupeBag.
 *
 * @since 0.5
 * @internal
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class NullDedupeBag implements DedupeBag {

	/**
	 * @see DedupeBag::alreadySeen
	 *
	 * Always returns false.
	 *
	 * @param string $hash
	 * @param string $namespace
	 *
	 * @return bool
	 */
	public function alreadySeen( $hash, $namespace = '' ) {
		return false;
	}

}
