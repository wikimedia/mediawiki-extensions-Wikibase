<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch;

/**
 * @license GPL-2.0-or-later
 */
class SimpleItemSearchRequest {

	private string $query;
	private string $language;

	public function __construct( string $query, string $language ) {
		$this->query = $query;
		$this->language = $language;
	}

	public function getQuery(): string {
		return $this->query;
	}

	public function getLanguage(): string {
		return $this->language;
	}

}
