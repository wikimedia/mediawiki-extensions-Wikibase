<?php declare( strict_types = 1 );

namespace Wikibase\Lib\Store;

use Wikibase\Lib\TermIndexEntry;

/**
 * Methods helping search interactors from a term store.
 *
 * @license GPL-2.0-or-later
 */
interface MatchingTermsLookup {

	/**
	 * @param string $termText The text to look up the term by
	 * @param string $entityType One of Item::ENTITY_TYPE or Property::ENTITY_TYPE
	 * @param string|string[]|null $searchLanguage The language(s) to search in, in order of pref
	 * @param string|string[]|null $termType The type of term(s) to search for, see {@link TermTypes}
	 * @param array $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *        - LIMIT: int, defaults to none
	 *        - OFFSET: int, defaults to none
	 *
	 * @return TermIndexEntry[]
	 */
	public function getMatchingTerms(
		string $termText,
		string $entityType,
		$searchLanguage = null,
		$termType = null,
		array $options = []
	): array;

}
