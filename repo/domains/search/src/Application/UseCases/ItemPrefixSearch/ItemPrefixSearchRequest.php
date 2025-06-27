<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch;

/**
 * @license GPL-2.0-or-later
 */
class ItemPrefixSearchRequest {

	public const DEFAULT_LIMIT = 10;
	public const DEFAULT_OFFSET = 0;

	public function __construct(
		public readonly string $query,
		public readonly string $language,
		public readonly int $limit = self::DEFAULT_LIMIT,
		public readonly int $offset = self::DEFAULT_OFFSET
	) {
	}

}
