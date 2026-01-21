<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch;

use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResultSet;

/**
 * @license GPL-2.0-or-later
 */
class FacetedItemSearchResponse {

	public function __construct(
		public readonly ItemSearchResultSet $results
	) {
	}
}
