<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch;

use Wikibase\Repo\Domains\Search\Domain\Services\PropertyPrefixSearchEngine;

/**
 * @license GPL-2.0-or-later
 */
class PropertyPrefixSearch {

	public function __construct(
		private PropertyPrefixSearchEngine $searchEngine
	) {
	}

	public function execute( PropertyPrefixSearchRequest $searchRequest ): PropertyPrefixSearchResponse {
		return new PropertyPrefixSearchResponse( $this->searchEngine->suggestProperties(
			$searchRequest->query,
			$searchRequest->language,
			$searchRequest->limit,
			$searchRequest->offset
		) );
	}
}
