<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch;

use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Reuse\Domain\Services\FacetedItemSearchEngine;

/**
 * @license GPL-2.0-or-later
 */
class FacetedItemSearch {

	public function __construct(
		private readonly FacetedItemSearchValidator $validator,
		private readonly FacetedItemSearchEngine $searchEngine,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( FacetedItemSearchRequest $request ): FacetedItemSearchResponse {
		$this->validator->validate( $request );
		$query = $this->validator->getValidatedQuery();

		return new FacetedItemSearchResponse(
			$this->searchEngine->search( $query )
		);
	}
}
