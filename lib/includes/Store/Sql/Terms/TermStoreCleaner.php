<?php

namespace Wikibase\Lib\Store\Sql\Terms;

/**
 * Interface for deleting IDs acquired from a {@link TermInLangIdsAcquirer},
 * including any further cleanup if necessary.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
interface TermStoreCleaner {

	/**
	 * Delete the given term in lang IDs.
	 * Ensuring that they are unreferenced is the caller’s responsibility.
	 *
	 * Depending on the implementation,
	 * this may include further internal cleanups.
	 * In that case, the implementation takes care
	 * that those cleanups do not affect other (not deleted) term in lang IDs.
	 *
	 * @param int[] $termInLangIds
	 */
	public function cleanTermInLangIds( array $termInLangIds );

}
