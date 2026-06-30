<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Services;

use Wikibase\Repo\Api\EntitySearchException;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySimpleSearchResults;

/**
 * @license GPL-2.0-or-later
 */
interface PropertySearchEngine {

	/**
	 * @throws EntitySearchException
	 */
	public function searchPropertyByLabel(
		string $searchTerm,
		string $languageCode,
		int $limit,
		int $offset
	): PropertySimpleSearchResults;
}
