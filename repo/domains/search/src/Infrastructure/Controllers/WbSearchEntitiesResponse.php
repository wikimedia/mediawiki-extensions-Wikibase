<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\Controllers;

use Wikibase\Lib\Interactors\TermSearchResult;

/**
 * @license GPL-2.0-or-later
 */
class WbSearchEntitiesResponse {

	/**
	 * @param TermSearchResult[] $results
	 */
	public function __construct(
		public readonly array $results,
		public readonly bool $hasMore,
	) {
	}
}
