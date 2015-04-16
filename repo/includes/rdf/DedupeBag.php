<?php

namespace Wikibase;

/**
 * Interface for a facility that avoids duplicates based on value hashes.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface DedupeBag {

	/**
	 * Did we already see this value? If yes, we may need to skip it
	 *
	 * @note False negatives are acceptable, while false positives are not.
	 * This means that implementations are free to return false if it is not
	 * sure whether the hash was seen before, but should never return true
	 * if it is not certain that the hash was need before.
	 *
	 * @param string $hash Hash to check
	 * @param string $namespace Optional namespace to allow a compartmentalized bag,
	 *        tracking hashes from multiple value sets.
	 *
	 * @return bool
	 */
	public function alreadySeen( $hash, $namespace = '' );

}
