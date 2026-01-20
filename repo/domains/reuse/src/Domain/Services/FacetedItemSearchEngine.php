<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Services;

use Wikibase\Repo\Domains\Reuse\Domain\Model\AndOperation;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResultSet;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValueFilter;

/**
 * @license GPL-2.0-or-later
 */
interface FacetedItemSearchEngine {

	public function search( AndOperation|PropertyValueFilter $query, int $limit, int $offset ): ItemSearchResultSet;
}
