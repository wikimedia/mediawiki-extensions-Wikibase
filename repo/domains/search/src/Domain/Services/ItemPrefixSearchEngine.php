<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Services;

use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;

/**
 * @license GPL-2.0-or-later
 */
interface ItemPrefixSearchEngine {
	public function suggestItems(
		string $searchTerm,
		string $languageCode,
		int $limit,
		int $offset
	): ItemSearchResults;
}
