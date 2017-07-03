<?php

namespace Wikibase\Repo\Api;

use Wikibase\Lib\Interactors\TermSearchResult;

/**
 * Generic interface for searching entities.
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
	 *
	 * @return TermSearchResult[] Key: string Serialized EntityId
	 */
	public function getRankedSearchResults(
		$text,
		$languageCode,
		$entityType,
		$limit,
		$strictLanguage
	);

}
