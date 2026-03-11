<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Services;

use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchFilter;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemSearchResultSet;

/**
 * @license GPL-2.0-or-later
 */
interface FacetedItemSearchEngine {

	public function search( ItemSearchFilter $query, int $limit, int $offset ): ItemSearchResultSet;
}
