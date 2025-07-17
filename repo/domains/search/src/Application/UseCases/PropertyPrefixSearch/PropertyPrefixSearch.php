<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch;

use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Domain\Services\PropertyPrefixSearchEngine;

/**
 * @license GPL-2.0-or-later
 */
class PropertyPrefixSearch {

	public function __construct(
		private PropertyPrefixSearchValidator $validator,
		private PropertyPrefixSearchEngine $searchEngine
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( PropertyPrefixSearchRequest $searchRequest ): PropertyPrefixSearchResponse {
		$this->validator->validate( $searchRequest );

		return new PropertyPrefixSearchResponse( $this->searchEngine->suggestProperties(
			$searchRequest->query,
			$searchRequest->language,
			$searchRequest->limit,
			$searchRequest->offset
		) );
	}
}
