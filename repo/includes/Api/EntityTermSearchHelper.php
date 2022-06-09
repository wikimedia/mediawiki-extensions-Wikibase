<?php

namespace Wikibase\Repo\Api;

use Wikibase\Lib\Interactors\ConfigurableTermSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchOptions;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\TermIndexEntry;

/**
 * Helper class to search for entities.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityTermSearchHelper implements EntitySearchHelper {

	/**
	 * @var ConfigurableTermSearchInteractor
	 */
	private $termSearchInteractor;

	/**
	 * @param ConfigurableTermSearchInteractor $termSearchInteractor
	 */
	public function __construct(
		ConfigurableTermSearchInteractor $termSearchInteractor
	) {
		$this->termSearchInteractor = $termSearchInteractor;
	}

	/**
	 * Gets exact matches. If there are not enough exact matches, it gets prefixed matches.
	 *
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param int $limit
	 * @param bool $strictLanguage
	 * @param string|null $profileContext
	 *
	 * @return TermSearchResult[] Key: string Serialized EntityId
	 */
	public function getRankedSearchResults(
		$text,
		$languageCode,
		$entityType,
		$limit,
		$strictLanguage,
		?string $profileContext
	) {
		$allSearchResults = [];

		$exactSearchResults = $this->searchEntities(
			$text,
			$languageCode,
			$entityType,
			$limit,
			false,
			$strictLanguage
		);
		$allSearchResults = $this->mergeSearchResults( $allSearchResults, $exactSearchResults, $limit );

		// If still not enough matched then search for prefix matches
		$missing = $limit - count( $allSearchResults );
		if ( $missing > 0 ) {
			$prefixSearchResults = $this->searchEntities(
				$text,
				$languageCode,
				$entityType,
				$limit, // needs to be the full limit as exact matches are also contained in the prefix search
				true,
				$strictLanguage
			);
			$allSearchResults = $this->mergeSearchResults( $allSearchResults, $prefixSearchResults, $limit );
		}

		return $allSearchResults;
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
	 * Wrapper around TermSearchInteractor::searchForEntities
	 *
	 * @see TermSearchInteractor::searchForEntities
	 *
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param int $limit
	 * @param bool $prefixSearch
	 * @param bool $strictLanguage
	 *
	 * @return TermSearchResult[]
	 */
	private function searchEntities( $text, $languageCode, $entityType, $limit, $prefixSearch, $strictLanguage ) {
		$searchOptions = new TermSearchOptions();
		$searchOptions->setLimit( $limit );
		$searchOptions->setIsPrefixSearch( $prefixSearch );
		$searchOptions->setIsCaseSensitive( false );
		$searchOptions->setUseLanguageFallback( !$strictLanguage );

		$this->termSearchInteractor->setTermSearchOptions( $searchOptions );

		return $this->termSearchInteractor->searchForEntities(
			$text,
			$languageCode,
			$entityType,
			[ TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS ]
		);
	}

}
