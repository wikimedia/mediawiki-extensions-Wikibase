<?php

namespace Wikibase\Repo\Api;

use Wikibase\Lib\Interactors\TermSearchResult;
use Wikimedia\Assert\Assert;

/**
 * Helper class to search for entities by ID
 *
 * @license GPL-2.0-or-later
 */
class CombinedEntitySearchHelper implements EntitySearchHelper {

	/**
	 * @var EntitySearchHelper[]
	 */
	private $searchHelpers;

	/**
	 * @param array $searchHelpers ordered array of EntitySearchHelpers to be used.
	 */
	public function __construct( array $searchHelpers ) {
		Assert::parameterElementType( EntitySearchHelper::class, $searchHelpers, '$searchHelpers' );

		$this->searchHelpers = $searchHelpers;
	}

	/**
	 * @inheritDoc
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

		foreach ( $this->searchHelpers as $helper ) {
			$newResults = $helper->getRankedSearchResults(
				$text,
				$languageCode,
				$entityType,
				$limit - count( $allSearchResults ),
				$strictLanguage,
				$profileContext
			);
			$allSearchResults = $this->mergeSearchResults( $allSearchResults, $newResults, $limit );

			// If we have already hit the correct number of results then stop looping through helpers
			if ( $limit - count( $allSearchResults ) <= 0 ) {
				break;
			}
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

}
