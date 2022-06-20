<?php

namespace Wikibase\Repo\Api;

use Wikibase\Lib\Interactors\TermSearchResult;

/**
 * Generic interface for searching entities.
 * @license GPL-2.0-or-later
 */
interface EntitySearchHelper {

	/**
	 * Get entities matching the search term.
	 *
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param int $limit
	 * @param bool $strictLanguage
	 * @param string|null $profileContext indication given to the engine on the context this search is made (might affect ranking)
	 *
	 * @return TermSearchResult[] Key: string Serialized EntityId
	 * @throws EntitySearchException when a problem occurs fetching data from the underlying datastore
	 */
	public function getRankedSearchResults(
		$text,
		$languageCode,
		$entityType,
		$limit,
		$strictLanguage,
		?string $profileContext
	);

}
