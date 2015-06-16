<?php

namespace Wikibase\Repo\Interactors;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\LabelDescriptionLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\TermLookup;
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
	 * @var LabelDescriptionLookup|null
	 */
	private $labelDescriptionLookup;

	/**
	 * @param TermIndex $termIndex
	 * @param TermLookup|null $termLookup
	 * @param LanguageFallbackChain|null $languageFallbackChain used to provide display terms
	 */
	public function __construct(
		TermIndex $termIndex,
		TermLookup $termLookup = null,
		LanguageFallbackChain $languageFallbackChain = null
	) {
		$this->termIndex = $termIndex;
		if ( $termLookup !== null && $languageFallbackChain !== null ) {
			$this->labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
				$termLookup,
				$languageFallbackChain
			);
		}
	}

	/**
	 * @param string $text
	 * @param string[] $languageCodes
	 * @param string $entityType
	 * @param string[] $termTypes Elements are Term::TYPE_*
	 * @param array $options
	 *        Accepted options are:
	 *        - caseSensitive: boolean, default true
	 *        - prefixSearch: boolean, default false
	 *        - LIMIT: int, defaults to none, maximum 5000
	 *
	 * @returns array[] array of arrays containing the following:
	 *              [0] => EntityId
	 *              [1] => MatchedTerm
	 *              [2] => DisplayTerms
	 */
	public function searchForTerms(
		$text,
		array $languageCodes,
		$entityType,
		array $termTypes,
		array $options = array()
	) {
		$matchedTerms =  $this->termIndex->getMatchingTerms(
			$this->getTerms( $text, $languageCodes, $termTypes ),
			null,
			$entityType,
			$this->enforceOptionsLimit( $options )
		);
		return $this->getSearchResult( $matchedTerms );
	}

	/**
	 * @param Term[] $terms
	 *
	 * @returns array[] array of arrays containing the following:
	 *              [0] => EntityId
	 *              [1] => MatchedTerm
	 *              [2] => DisplayTerms
	 */
	private function getSearchResult( $terms ) {
		$searchResults = array();
		foreach ( $terms as $term ) {
			$entityId = $term->getEntityId();
			$searchResult = array( $entityId, $term, );
			if ( isset( $this->labelDescriptionLookup ) ) {
				$searchResult[] = $this->getDisplayTerms( $entityId );
			}
			$searchResults[] = $searchResult;
		}
		return $searchResults;
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
	 * @param array $options
	 *
	 * @return array
	 */
	private function enforceOptionsLimit( array $options ) {
		if ( isset( $options['LIMIT'] ) && $options['LIMIT'] > 5000 ) {
			$options['LIMIT'] = 5000;
		}
		return $options;
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
				$this->getTerm( $text, $languageCode, $termType );
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
