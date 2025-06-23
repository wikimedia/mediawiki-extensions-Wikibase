<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch;

use Wikibase\Repo\Domains\Search\Domain\Services\ItemPrefixSearchEngine;

/**
 * @license GPL-2.0-or-later
 */
class ItemPrefixSearch {

	public function __construct(
		private ItemPrefixSearchValidator $validator,
		private ItemPrefixSearchEngine $searchEngine
	) {
	}

	public function execute( ItemPrefixSearchRequest $itemRequest ): ItemPrefixSearchResponse {
		$this->validator->validate( $itemRequest );

		return new ItemPrefixSearchResponse( $this->searchEngine->suggestItems(
			$itemRequest->query,
			$itemRequest->language,
			$itemRequest->limit,
			$itemRequest->offset
		) );
	}
}
