<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch;

use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;

/**
 * @license GPL-2.0-or-later
 */
class SimpleItemSearchResponse {
	public function __construct( public readonly ItemSearchResults $results ) {
	}
}
