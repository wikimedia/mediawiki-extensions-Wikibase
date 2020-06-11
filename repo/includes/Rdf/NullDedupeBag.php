<?php

namespace Wikibase\Repo\Rdf;

/**
 * Null implementation of DedupeBag.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 *
 * @codeCoverageIgnore
 */
class NullDedupeBag implements DedupeBag {

	/**
	 * @see DedupeBag::alreadySeen
	 *
	 * Always returns false, indicating that the hash has not be seen before, and the associated
	 * data needs to be processed again. This would generate a false negative whenever the
	 * method is called twice with the same parameters. This is admissible by the contract
	 * of the method, which explicitly allows false negatives. The consequence may be that
	 * the caller redundantly processes data that had been processed before.
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
