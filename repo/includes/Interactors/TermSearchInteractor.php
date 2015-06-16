<?php

namespace Wikibase\Repo\Interactors;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\LabelDescriptionLookup;
use Wikibase\Term;
use Wikibase\TermIndex;

/**
 * Enables searching for Entities or Terms by providing a Term, collection of Terms and options
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class TermSearchInteractor {

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @var bool
	 */
	private $isCaseSensitive;

	/**
	 * @var bool
	 */
	private $isPrefixSearch;

	/**
	 * @var int
	 */
	private $limit;

	/**
	 * @var LabelDescriptionLookup|null
	 */
	private $labelDescriptionLookup;

	/**
	 * @param TermIndex $termIndex
	 * @param bool $caseSensitive
	 * @param bool $prefixSearch
	 * @param int $limit Default to 5000, Hard upper limit of 5000
	 * @param LabelDescriptionLookup|null $labelDescriptionLookup used to provide display terms
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		TermIndex $termIndex,
		$caseSensitive,
		$prefixSearch,
		$limit = 5000,
		LabelDescriptionLookup $labelDescriptionLookup = null
	) {
		$this->throwExceptionOnBadOptions( $caseSensitive, $prefixSearch, $limit );
		$this->termIndex = $termIndex;
		$this->isCaseSensitive = $caseSensitive;
		$this->isPrefixSearch = $prefixSearch;
		if ( $limit > 5000 ) {
			$limit = 5000;
		}
		$this->limit = $limit;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
	}

	private function throwExceptionOnBadOptions( $caseSensitive, $prefixSearch, $limit ) {
		if ( !is_bool( $caseSensitive ) ) {
			throw new InvalidArgumentException( '$caseSensitive must be a boolean' );
		}
		if ( !is_bool( $prefixSearch ) ) {
			throw new InvalidArgumentException( '$prefixSearch must be a boolean' );
		}
		if ( !is_int( $limit ) ) {
			throw new InvalidArgumentException( '$limit must be a integer' );
		}
	}

	/**
	 * @param string $text
	 * @param string[] $languageCodes
	 * @param string $entityType
	 * @param string[] $termTypes Elements are Term::TYPE_*
	 *
	 * @returns array[] array of arrays containing the following:
	 *              [0] => EntityId
	 *              [1] => MatchedTerm
	 *              [2] => DisplayTerms (optional)
	 */
	public function searchForTerms( $text, array $languageCodes, $entityType, array $termTypes ) {
		// TODO search using some form of langugae fallback in the search
		$matchedTerms =  $this->termIndex->getMatchingTerms(
			$this->getTerms( $text, $languageCodes, $termTypes ),
			null,
			$entityType,
			$this->getTermIndexOptions()
		);
		return $this->getSearchResults( $matchedTerms );
	}

	/**
	 * @param Term[] $terms
	 *
	 * @returns array[] array of arrays containing the following:
	 *              [0] => EntityId
	 *              [1] => MatchedTerm
	 *              [2] => DisplayTerms (optional)
	 */
	private function getSearchResults( array $terms ) {
		$searchResults = array();
		foreach ( $terms as $term ) {
			$searchResults[] = $this->getSearchResult( $term );
		}
		return $searchResults;
	}

	/**
	 * @param Term $term
	 *
	 * @return array the the following elements:
	 *              [0] => EntityId
	 *              [1] => MatchedTerm
	 *              [2] => DisplayTerms (optional)
	 */
	private function getSearchResult( Term $term ) {
		$entityId = $term->getEntityId();
		$searchResult = array( $entityId, $term, );
		if ( $this->labelDescriptionLookup !== null ) {
			$searchResult[] = $this->getDisplayTerms( $entityId );
		}
		return $searchResult;
	}

	private function getTermIndexOptions() {
		return array(
			'caseSensitive' => $this->isCaseSensitive,
			'prefixSearch' => $this->isPrefixSearch,
			'LIMIT' => $this->limit,
		);
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return Term[]
	 */
	private function getDisplayTerms( EntityId $entityId ) {
		return array(
			$this->labelDescriptionLookup->getLabel( $entityId ),
			$this->labelDescriptionLookup->getDescription( $entityId ),
		);
	}

	/**
	 * @param string $text
	 * @param string[] $languageCodes
	 * @param string[] $termTypes
	 *
	 * @returns Term[]
	 */
	private function getTerms( $text, $languageCodes, $termTypes ) {
		$terms = array();
		foreach ( $languageCodes as $languageCode ) {
			foreach ( $termTypes as $termType ) {
				$terms[] = $this->getTerm( $text, $languageCode, $termType );
			}
		}
		return $terms;
	}

	/**
	 * @param string $text
	 * @param string $languageCode
	 * @param string $termType
	 *
	 * @returns Term
	 */
	private function getTerm( $text, $languageCode, $termType ) {
		return new Term( array(
			'termText' => $text,
			'termLanguage' => $languageCode,
			'termType' => $termType,
		) );
	}

}
