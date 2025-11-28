<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Domains\Reuse\Domain\Model\AndOperation;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValueFilter;
use Wikibase\Repo\Domains\Reuse\Domain\Services\FacetedItemSearchEngine;

/**
 * @license GPL-2.0-or-later
 */
class FacetedItemSearch {

	public function __construct(
		private readonly FacetedItemSearchEngine $searchEngine
	) {
	}

	public function execute(
		FacetedItemSearchRequest $request
	): FacetedItemSearchResponse {
		// TODO: validate
		$query = $this->constructQuery( $request->query );

		return new FacetedItemSearchResponse(
			$this->searchEngine->search( $query )
		);
	}

	private function constructQuery( array $filter ): AndOperation|PropertyValueFilter {
		if ( isset( $filter['property'] ) ) {
			return new PropertyValueFilter(
				new NumericPropertyId( $filter['property'] ),
				$filter['value'] ?? null
			);
		}

		return new AndOperation(
			array_map(
				$this->constructQuery( ... ),
				$filter['and']
			)
		);
	}
}
