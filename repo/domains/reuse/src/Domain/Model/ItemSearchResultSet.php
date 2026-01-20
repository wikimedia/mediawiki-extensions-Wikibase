<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class ItemSearchResultSet {

	public function __construct( public readonly array $results, public readonly int $totalResults ) {
	}
}
