<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch;

use Wikibase\Repo\Domains\Search\Domain\Services\ItemSearchEngine;

/**
 * @license GPL-2.0-or-later
 */
class SimpleItemSearch {

	private ItemSearchEngine $searchEngine;

	public function __construct( ItemSearchEngine $searchEngine ) {
		$this->searchEngine = $searchEngine;
	}

	public function execute( SimpleItemSearchRequest $itemRequest ): SimpleItemSearchResponse {
		$searchTerm = $itemRequest->getQuery();
		$language = $itemRequest->getLanguage();

		return new SimpleItemSearchResponse( $this->searchEngine->searchItemByLabel( $searchTerm, $language ) );
	}

}
