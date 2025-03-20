<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch;

use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResults;

/**
 * @license GPL-2.0-or-later
 */
class SimplePropertySearchResponse {
	private PropertySearchResults $results;

	public function __construct( PropertySearchResults $results ) {
		$this->results = $results;
	}

	public function getResults(): PropertySearchResults {
		return $this->results;
	}
}
