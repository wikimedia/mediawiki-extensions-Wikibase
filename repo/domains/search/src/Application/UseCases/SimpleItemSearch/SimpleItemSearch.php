<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch;

use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Domain\Services\ItemSearchEngine;

/**
 * @license GPL-2.0-or-later
 */
class SimpleItemSearch {

	private SimpleItemSearchValidator $validator;
	private ItemSearchEngine $searchEngine;

	public function __construct(
		SimpleItemSearchValidator $validator,
		ItemSearchEngine $searchEngine
	) {
		$this->validator = $validator;
		$this->searchEngine = $searchEngine;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( SimpleItemSearchRequest $itemRequest ): SimpleItemSearchResponse {
		$this->validator->validate( $itemRequest );

		return new SimpleItemSearchResponse( $this->searchEngine->searchItemByLabel(
			$itemRequest->query,
			$itemRequest->language,
			$itemRequest->limit,
			$itemRequest->offset
		) );
	}
}
