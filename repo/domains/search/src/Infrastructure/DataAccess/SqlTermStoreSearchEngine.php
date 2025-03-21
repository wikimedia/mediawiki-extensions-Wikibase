<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\Lib\TermIndexEntry;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Domain\Model\MatchedData;
use Wikibase\Repo\Domains\Search\Domain\Services\ItemSearchEngine;

/**
 * Sql-backed {@link ItemSearchEngine}. This should only be used for development or testing purposes.
 *
 * @license GPL-2.0-or-later
 */
class SqlTermStoreSearchEngine implements ItemSearchEngine {
	private const RESULTS_LIMIT = 5;

	private MatchingTermsLookup $matchingTermsLookup;
	private TermRetriever $termRetriever;
	private LanguageFallbackChainFactory $languageFallbackChainFactory;

	public function __construct(
		MatchingTermsLookup $matchingTermsLookup,
		TermRetriever $termRetriever,
		LanguageFallbackChainFactory $languageFallbackChainFactory
	) {
		$this->matchingTermsLookup = $matchingTermsLookup;
		$this->termRetriever = $termRetriever;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
	}

	public function searchItemByLabel( string $searchTerm, string $languageCode ): ItemSearchResults {
		return new ItemSearchResults( ...array_map(
			function ( TermIndexEntry $entry ) use ( $languageCode ) {
				$matchedTermAsLabel = new Label( $entry->getLanguage(), $entry->getText() );
				// if the matching entry is a label
				$itemLabel = $entry->getTermType() === TermTypes::TYPE_LABEL ?
					// then use it for the search result
					$matchedTermAsLabel :
					// otherwise look up the item label in search language
					$this->termRetriever->getLabel( $entry->getEntityId(), $languageCode );
				return new ItemSearchResult(
					new ItemId( (string)$entry->getEntityId() ),
					$itemLabel ?? $matchedTermAsLabel,
					$this->termRetriever->getDescription( $entry->getEntityId(), $languageCode ),
					new MatchedData( $entry->getTermType(), $entry->getLanguage(), $entry->getText() )
				);
			},
			$this->findMatchingLabelsAndAliases( $searchTerm, $languageCode )
		) );
	}

	/**
	 * @return TermIndexEntry[]
	 */
	private function findMatchingLabelsAndAliases( string $searchTerm, string $languageCode ): array {
		$searchCriteria = array_map(
			fn( string $lang ) => new TermIndexSearchCriteria( [ 'termLanguage' => $lang, 'termText' => $searchTerm ] ),
			$this->languageFallbackChainFactory->newFromLanguageCode( $languageCode )->getFetchLanguageCodes()
		);

		return array_merge(
			$this->matchingTermsLookup->getMatchingTerms(
				$searchCriteria,
				TermTypes::TYPE_LABEL,
				Item::ENTITY_TYPE,
				[ 'LIMIT' => self::RESULTS_LIMIT ]
			),
			$this->matchingTermsLookup->getMatchingTerms(
				$searchCriteria,
				TermTypes::TYPE_ALIAS,
				Item::ENTITY_TYPE,
				[ 'LIMIT' => self::RESULTS_LIMIT ]
			)
		);
	}

}
