<?php

namespace Wikibase\Repo\Api;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Interactors\TermIndexSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchOptions;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\TermIndexEntry;

/**
 * Helper class to search for entities.
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntitySearchHelper {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var TermIndexSearchInteractor
	 */
	private $termIndexSearchInteractor;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	public function __construct(
		EntityTitleLookup $titleLookup,
		EntityIdParser $idParser,
		TermIndexSearchInteractor $termIndexSearchInteractor,
		LabelDescriptionLookup $labelDescriptionLookup
	) {
		$this->titleLookup = $titleLookup;
		$this->idParser = $idParser;
		$this->termIndexSearchInteractor = $termIndexSearchInteractor;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
	}

	/**
	 * Gets exact matches. If there are not enough exact matches, it gets prefixed matches.
	 *
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param int $limit
	 * @param bool $strictLanguage
	 *
	 * @return TermSearchResult[] Key: string Serialized EntityId
	 */
	public function getRankedSearchResults( $text, $languageCode, $entityType, $limit, $strictLanguage ) {
		$allSearchResults = array();

		// If $text is the ID of an existing item, include it in the result.
		$entityId = $this->getExactMatchForEntityId( $text, $entityType );
		if ( $entityId !== null ) {
			// This is nothing to do with terms, but make it look a normal result so everything is easier
			$displayTerms = $this->getDisplayTerms( $entityId );
			$allSearchResults[$entityId->getSerialization()] = new TermSearchResult(
				new Term( 'qid', $entityId->getSerialization() ),
				'entityId',
				$entityId,
				$displayTerms['label'],
				$displayTerms['description']
			);
		}

		// If not matched enough then search for full term matches
		$missing = $limit - count( $allSearchResults );

		if ( $missing > 0 ) {
			$searchOptions = $this->getSearchOptions( $missing, false, $strictLanguage );

			$exactSearchResults = $this->searchEntities(
				$text,
				$languageCode,
				$entityType,
				$this->getSearchOptions( $missing, false, $strictLanguage )
			);

			$allSearchResults = $this->mergeSearchResults(
				$allSearchResults,
				$exactSearchResults,
				$limit
			);

			// If still not enough matched then search for prefix matches
			$missing = $limit - count( $allSearchResults );

			if ( $missing > 0 ) {
				// needs to be the full limit as exact matches are also contained in the prefix search
				$prefixSearchResults = $this->searchEntities(
					$text,
					$languageCode,
					$entityType,
					$this->getSearchOptions( $limit, true, $strictLanguage )
				);

				$allSearchResults = $this->mergeSearchResults(
					$allSearchResults,
					$prefixSearchResults,
					$limit
				);
			}
		}

		return $allSearchResults;
	}

	/**
	 * Gets exact match for the search term as an EntityId if it can be found.
	 *
	 * @param string $term
	 * @param string $entityType
	 *
	 * @return EntityId|null
	 */
	private function getExactMatchForEntityId( $term, $entityType ) {
		try {
			$entityId = $this->idParser->parse( $term );
			$title = $this->titleLookup->getTitleForId( $entityId );

			if ( $title && $title->exists() && ( $entityId->getEntityType() === $entityType ) ) {
				return $entityId;
			}
		} catch ( EntityIdParsingException $ex ) {
			// never mind, doesn't look like an ID.
		}

		return null;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return Term[] array with keys 'label' and 'description'
	 */
	private function getDisplayTerms( EntityId $entityId ) {
		$displayTerms = array();

		$displayTerms['label'] = $this->labelDescriptionLookup->getLabel( $entityId );
		$displayTerms['description'] = $this->labelDescriptionLookup->getDescription( $entityId );

		return $displayTerms;
	}

	/**
	 * @param TermSearchResult[] $searchResults
	 * @param TermSearchResult[] $newSearchResults
	 * @param int $limit
	 *
	 * @return TermSearchResult[]
	 */
	private function mergeSearchResults( array $searchResults, array $newSearchResults, $limit ) {
		$searchResultEntityIdSerializations = array_keys( $searchResults );

		foreach ( $newSearchResults as $searchResultToAdd ) {
			$entityIdString = $searchResultToAdd->getEntityId()->getSerialization();

			if ( !in_array( $entityIdString, $searchResultEntityIdSerializations ) ) {
				$searchResults[$entityIdString] = $searchResultToAdd;
				$searchResultEntityIdSerializations[] = $entityIdString;
				$missing = $limit - count( $searchResults );

				if ( $missing <= 0 ) {
					return $searchResults;
				}
			}
		}

		return $searchResults;
	}

	/**
	 * @param int $limit
	 * @param bool $prefixSearch
	 * @param bool $strictLanguage
	 *
	 * @return TermSearchOptions
	 */
	private function getSearchOptions( $limit, $prefixSearch, $strictLanguage ) {
		$searchOptions = new TermSearchOptions();

		$searchOptions->setLimit( $limit );
		$searchOptions->setIsPrefixSearch( $prefixSearch );
		$searchOptions->setIsCaseSensitive( false );
		$searchOptions->setUseLanguageFallback( !$strictLanguage );

		return $searchOptions;
	}

	/**
	 * Wrapper around TermSearchInteractor::searchForEntities
	 *
	 * @see TermSearchInteractor::searchForEntities
	 *
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param TermSearchOptions $searchOptions
	 *
	 * @return TermSearchResult[]
	 */
	private function searchEntities(
		$text,
		$languageCode,
		$entityType,
		TermSearchOptions $searchOptions
	) {
		$this->termIndexSearchInteractor->setTermSearchOptions( $searchOptions );

		return $this->termIndexSearchInteractor->searchForEntities(
			$text,
			$languageCode,
			$entityType,
			array( TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS )
		);
	}

}
