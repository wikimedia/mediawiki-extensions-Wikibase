<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\TermIndexEntry;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
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
	private EntityLookup $entityLookup;
	private TermRetriever $termRetriever;
	private LanguageFallbackChainFactory $languageFallbackChainFactory;

	public function __construct(
		MatchingTermsLookup $matchingTermsLookup,
		EntityLookup $entityLookup,
		TermRetriever $termRetriever,
		LanguageFallbackChainFactory $languageFallbackChainFactory
	) {
		$this->matchingTermsLookup = $matchingTermsLookup;
		$this->entityLookup = $entityLookup;
		$this->termRetriever = $termRetriever;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
	}

	public function searchItemByLabel(
		string $searchTerm,
		string $languageCode,
		int $limit,
		int $offset
	): ItemSearchResults {
		$results = [];
		$resultById = $this->searchItemById( $searchTerm, $languageCode );
		if ( $resultById ) {
			// when an Item was found by ID
			if ( $offset === 0 ) {
				// add it on top of the first page and reduce limit by one
				$results[] = $resultById;
				$limit--;
			} else {
				// reduce offset by one on consecutive pages
				$offset--;
			}
		}
		return new ItemSearchResults(
			...$results,
			...array_map(
				$this->convertResult( ItemSearchResult::class, $languageCode ),
				$this->findMatchingLabelsAndAliases( Item::ENTITY_TYPE, $searchTerm, $languageCode, $limit, $offset )
			)
		);
	}

	public function searchPropertyByLabel(
		string $searchTerm,
		string $languageCode,
		int $limit,
		int $offset
	): PropertySearchResults {
		$results = [];
		$resultById = $this->searchPropertyById( $searchTerm, $languageCode );
		if ( $resultById ) {
			// when a Property was found by ID
			if ( $offset === 0 ) {
				// add it on top of the first page and reduce limit by one
				$results[] = $resultById;
				$limit--;
			} else {
				// reduce offset by one on consecutive pages
				$offset--;
			}
		}
		return new PropertySearchResults(
			...$results,
			...array_map(
				$this->convertResult( PropertySearchResult::class, $languageCode ),
				$this->findMatchingLabelsAndAliases( Property::ENTITY_TYPE, $searchTerm, $languageCode, $limit, $offset )
			)
		);
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
		return $this->matchingTermsLookup->getMatchingTerms(
			$searchTerm,
			$entityType,
			$this->languageFallbackChainFactory->newFromLanguageCode( $languageCode )->getFetchLanguageCodes(),
			[ TermTypes::TYPE_LABEL, TermTypes::TYPE_ALIAS ],
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

	private function searchItemById( string $searchTerm, string $languageCode ): ?ItemSearchResult {
		$item = $this->lookupItemById( $searchTerm );
		if ( !$item ) {
			return null;
		}

		$fallbackChain = $this->languageFallbackChainFactory->newFromLanguageCode( $languageCode );
		$itemLabel = $fallbackChain->extractPreferredValue( $item->getLabels()->toTextArray() );
		$itemDescription = $fallbackChain->extractPreferredValue( $item->getDescriptions()->toTextArray() );
		return new ItemSearchResult(
			$item->getId(),
			$itemLabel ? new Label( $itemLabel['language'], $itemLabel['value'] ) : null,
			$itemDescription ? new Description( $itemDescription['language'], $itemDescription['value'] ) : null,
			new MatchedData( 'entityId', null, $item->getId()->getSerialization() )
		);
	}

	private function searchPropertyById( string $searchTerm, string $languageCode ): ?PropertySearchResult {
		$property = $this->lookupPropertyById( $searchTerm );
		if ( !$property ) {
			return null;
		}

		$fallbackChain = $this->languageFallbackChainFactory->newFromLanguageCode( $languageCode );
		$propertyLabel = $fallbackChain->extractPreferredValue( $property->getLabels()->toTextArray() );
		$propertyDescription = $fallbackChain->extractPreferredValue( $property->getDescriptions()->toTextArray() );
		return new PropertySearchResult(
			$property->getId(),
			$propertyLabel ? new Label( $propertyLabel['language'], $propertyLabel['value'] ) : null,
			$propertyDescription ? new Description( $propertyDescription['language'], $propertyDescription['value'] ) : null,
			new MatchedData( 'entityId', null, $property->getId()->getSerialization() )
		);
	}

	private function lookupItemById( string $searchTerm ): ?Item {
		try {
			$entityId = ( new BasicEntityIdParser() )->parse( $searchTerm );
		} catch ( EntityIdParsingException $ex ) {
			$entityId = null;
		}

		$item = $entityId instanceof ItemId ? $this->entityLookup->getEntity( $entityId ) : null;
		'@phan-var \Wikibase\DataModel\Entity\Item $item';

		return $item instanceof Item ? $item : null;
	}

	private function lookupPropertyById( string $searchTerm ): ?Property {
		try {
			$entityId = ( new BasicEntityIdParser() )->parse( $searchTerm );
		} catch ( EntityIdParsingException $ex ) {
			$entityId = null;
		}

		$property = $entityId instanceof PropertyId ? $this->entityLookup->getEntity( $entityId ) : null;
		'@phan-var \Wikibase\DataModel\Entity\Property $property';

		return $property instanceof Property ? $property : null;
	}

}
