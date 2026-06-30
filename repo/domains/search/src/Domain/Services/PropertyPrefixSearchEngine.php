<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Services;

use Wikibase\Repo\Api\EntitySearchException;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertyPrefixSearchResults;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyPrefixSearchEngine {

	/**
	 * @throws EntitySearchException
	 */
	public function suggestProperties(
		string $searchTerm,
		string $languageCode,
		int $limit,
		int $offset,
		bool $disableLanguageFallback,
		string $resultLanguageCode,
	): PropertyPrefixSearchResults;
}
