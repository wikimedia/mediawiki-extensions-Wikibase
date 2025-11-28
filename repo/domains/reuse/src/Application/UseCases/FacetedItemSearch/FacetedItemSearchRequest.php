<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch;

/**
 * @license GPL-2.0-or-later
 */
class FacetedItemSearchRequest {

	/**
	 * @param array<string,mixed> $query
	 */
	public function __construct(
		public readonly array $query
	) {
	}
}
