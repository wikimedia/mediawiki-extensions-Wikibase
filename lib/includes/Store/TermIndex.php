<?php

namespace Wikibase;

use Wikibase\Lib\Store\EntityTermStoreWriter;
use Wikibase\Lib\Store\MatchingTermsLookup;

/**
 * Interface to a cache for terms with both write and lookup methods.
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
