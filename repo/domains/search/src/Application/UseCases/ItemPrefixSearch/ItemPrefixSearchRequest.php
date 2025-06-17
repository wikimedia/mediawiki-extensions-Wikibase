<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch;

/**
 * @license GPL-2.0-or-later
 */
class ItemPrefixSearchRequest {

	private const DEFAULT_LIMIT = 10;
	private const DEFAULT_OFFSET = 0;

	private string $query;
	private string $language;
	private int $limit;
	private int $offset;

	public function __construct( string $query, string $language, ?int $limit, ?int $offset ) {
		$this->query = $query;
		$this->language = $language;
		$this->limit = $limit ?? self::DEFAULT_LIMIT;
		$this->offset = $offset ?? self::DEFAULT_OFFSET;
	}

	public function getQuery(): string {
		return $this->query;
	}

	public function getLanguage(): string {
		return $this->language;
	}

	public function getLimit(): int {
		return $this->limit;
	}

	public function getOffset(): int {
		return $this->offset;
	}

}
