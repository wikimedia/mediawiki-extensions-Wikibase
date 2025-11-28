<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Services;

use Wikibase\Repo\Domains\Reuse\Domain\Model\AndOperation;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValueFilter;

/**
 * @license GPL-2.0-or-later
 */
interface FacetedItemSearchEngine {

	/**
	 * @param AndOperation|PropertyValueFilter $query
	 *
	 * @return ItemSearchResult[]
	 */
	public function search( AndOperation|PropertyValueFilter $query ): array;
}
