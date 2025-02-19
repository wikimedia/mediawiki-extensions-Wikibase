<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\Lib\TermIndexEntry;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Services\ItemSearchEngine;

/**
 * Sql-backed {@link ItemSearchEngine}. This should only be used for development or testing purposes.
 *
 * @license GPL-2.0-or-later
 */
class SqlTermStoreSearchEngine implements ItemSearchEngine {
	private const RESULTS_LIMIT = 5;

	private MatchingTermsLookup $matchingTermsLookup;
	private TermLookup $termLookup;

	public function __construct( MatchingTermsLookup $matchingTermsLookup, TermLookup $termLookup ) {
		$this->matchingTermsLookup = $matchingTermsLookup;
		$this->termLookup = $termLookup;
	}

	public function searchItemByLabel( string $searchTerm, string $languageCode ): ItemSearchResults {
		return new ItemSearchResults( ...array_map(
			fn( TermIndexEntry $entry ) => new ItemSearchResult(
				new ItemId( (string)$entry->getEntityId() ),
				$entry->getTerm()->getText(),
				$this->termLookup->getDescription( $entry->getEntityId(), $languageCode ) ?? ''
			),
			$this->matchingTermsLookup->getMatchingTerms(
				[ new TermIndexSearchCriteria( [ 'termLanguage' => $languageCode, 'termText' => $searchTerm ] ) ],
				TermTypes::TYPE_LABEL,
				Item::ENTITY_TYPE,
				[ 'LIMIT' => self::RESULTS_LIMIT ]
			)
		) );
	}
}
