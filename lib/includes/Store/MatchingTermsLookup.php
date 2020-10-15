<?php

namespace Wikibase\Lib\Store;

use Wikibase\Lib\TermIndexEntry;

/**
 * Methods helping search interactors from a term store.
 *
 * @license GPL-2.0-or-later
 */
interface MatchingTermsLookup {

	/**
	 * Returns the terms that match the provided conditions.
	 *
	 * $terms is an array of Term objects. Terms are joined by OR.
	 * The fields of the terms are joined by AND.
	 *
	 * A default can be provided for termType and entityType via the corresponding
	 * method parameters.
	 *
	 * @param TermIndexSearchCriteria[] $criteria
	 * @param string|string[]|null $termType
	 * @param string|string[]|null $entityType
	 * @param array $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *        - LIMIT: int, defaults to none
	 *        - orderByWeight: boolean, default false
	 *
	 * @return TermIndexEntry[]
	 */
	public function getMatchingTerms(
		array $criteria,
		$termType = null,
		$entityType = null,
		array $options = []
	);

}
