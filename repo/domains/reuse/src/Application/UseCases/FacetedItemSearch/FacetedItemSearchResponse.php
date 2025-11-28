<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch;

use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResult;

/**
 * @license GPL-2.0-or-later
 */
class FacetedItemSearchResponse {

	/**
	 * @param ItemSearchResult[] $results
	 */
	public function __construct(
		public readonly array $results
	) {
	}
}
