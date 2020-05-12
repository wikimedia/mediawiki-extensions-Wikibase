<?php

namespace Wikibase\Lib\Store;

/**
 * Interface to a cache for terms with both write and lookup methods.
 *
 * @deprecated As wb_terms is going away, See https://phabricator.wikimedia.org/T208425
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface TermIndex extends EntityTermStoreWriter, LegacyEntityTermStoreReader, MatchingTermsLookup {

	/**
	 * Clears all terms from the cache.
	 *
	 * This is used both with the EntityTermStoreWriter methods and LegacyEntityTermStoreReader.
	 * Hence why it lives in this middle ground.
	 *
	 * @return boolean Success indicator
	 */
	public function clear();

}
