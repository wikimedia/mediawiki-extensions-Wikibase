<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Services;

use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResults;

/**
 * @license GPL-2.0-or-later
 */
interface PropertySearchEngine {
	public function searchPropertyByLabel( string $searchTerm, string $languageCode ): PropertySearchResults;
}
