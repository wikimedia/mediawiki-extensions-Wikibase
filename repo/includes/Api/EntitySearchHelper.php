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
	 * @param string $text Search term
	 * @param string $languageCode Search language
	 * @param string $entityType Entity type to search (item, property, etc.)
	 * @param int $limit Max number of entries to return
	 * @param array $options Search options
	 * @return TermSearchResult[] Key: string Serialized EntityId
	 */
	public function getRankedSearchResults(
		$text,
		$languageCode,
		$entityType,
		$limit,
		$options
	);

}
