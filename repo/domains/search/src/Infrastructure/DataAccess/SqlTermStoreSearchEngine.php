<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\Lib\TermIndexEntry;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\ItemSearchResults;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Domain\Model\MatchedData;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResult;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertySearchResults;
use Wikibase\Repo\Domains\Search\Domain\Services\ItemSearchEngine;
use Wikibase\Repo\Domains\Search\Domain\Services\PropertySearchEngine;

/**
 * Sql-backed {@link ItemSearchEngine} and {@link PropertySearchEngine}.
 * This should only be used for development or testing purposes.
 *
 * @license GPL-2.0-or-later
 */
class SqlTermStoreSearchEngine implements ItemSearchEngine, PropertySearchEngine {

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

	public function searchItemByLabel(
		string $searchTerm,
		string $languageCode,
		int $limit,
		int $offset
	): ItemSearchResults {
		return new ItemSearchResults( ...array_map(
			$this->convertResult( ItemSearchResult::class, $languageCode ),
			$this->findMatchingLabelsAndAliases( Item::ENTITY_TYPE, $searchTerm, $languageCode, $limit, $offset )
		) );
	}

	public function searchPropertyByLabel(
		string $searchTerm,
		string $languageCode,
		int $limit = 10,
		int $offset = 0
	): PropertySearchResults {
		return new PropertySearchResults( ...array_map(
			$this->convertResult( PropertySearchResult::class, $languageCode ),
			$this->findMatchingLabelsAndAliases( Property::ENTITY_TYPE, $searchTerm, $languageCode, $limit, $offset )
		) );
	}

	/**
	 * @return TermIndexEntry[]
	 */
	private function findMatchingLabelsAndAliases(
		string $entityType,
		string $searchTerm,
		string $languageCode,
		int $limit,
		int $offset
	): array {
		$searchCriteria = array_map(
			fn( string $lang ) => new TermIndexSearchCriteria( [ 'termLanguage' => $lang, 'termText' => $searchTerm ] ),
			$this->languageFallbackChainFactory->newFromLanguageCode( $languageCode )->getFetchLanguageCodes()
		);

		return $this->matchingTermsLookup->getMatchingTerms(
			$searchCriteria,
			[ TermTypes::TYPE_LABEL, TermTypes::TYPE_ALIAS ],
			$entityType,
			[ 'LIMIT' => $limit, 'OFFSET' => $offset ]
		);
	}

	private function convertResult( string $resultClass, string $languageCode ): callable {
		return function ( TermIndexEntry $entry ) use ( $resultClass, $languageCode ) {
			$matchedTermAsLabel = new Label( $entry->getLanguage(), $entry->getText() );
			$itemLabel = $entry->getTermType() === TermTypes::TYPE_LABEL ?
				$matchedTermAsLabel :
				$this->termRetriever->getLabel( $entry->getEntityId(), $languageCode );

			return new $resultClass(
				$entry->getEntityId(),
				$itemLabel ?? $matchedTermAsLabel,
				$this->termRetriever->getDescription( $entry->getEntityId(), $languageCode ),
				new MatchedData( $entry->getTermType(), $entry->getLanguage(), $entry->getText() )
			);
		};
	}

}
