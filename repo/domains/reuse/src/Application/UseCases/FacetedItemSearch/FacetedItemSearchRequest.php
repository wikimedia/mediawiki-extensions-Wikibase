<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch;

/**
 * @license GPL-2.0-or-later
 */
class FacetedItemSearchRequest {
	public const DEFAULT_LIMIT = 10;
	public const DEFAULT_OFFSET = 0;
	public const MAX_LIMIT = 50;
	public const MAX_OFFSET = 9999;

	/**
	 * @param array<string,mixed> $query
	 * @param int $limit
	 * @param int $offset
	 */
	public function __construct(
		public readonly array $query,
		public readonly int $limit = self::DEFAULT_LIMIT,
		public readonly int $offset = self::DEFAULT_OFFSET,
	) {
	}
}
