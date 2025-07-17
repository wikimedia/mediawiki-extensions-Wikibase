<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch;

use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResults;

/**
 * @license GPL-2.0-or-later
 */
class SimplePropertySearchResponse {
	public function __construct( public readonly PropertySearchResults $results ) {
	}
}
