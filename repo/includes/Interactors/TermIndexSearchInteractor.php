<?php

namespace Wikibase\Repo\Interactors;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LabelDescriptionLookup;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;
use Wikimedia\Assert\Assert;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class TermIndexSearchInteractor implements TermSearchInteractor {

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var bool
	 */
	private $isCaseSensitive = false;

	/**
	 * @var bool
	 */
	private $isPrefixSearch = false;

	/**
	 * @var int
	 */
	private $limit = 5000;

	/**
	 * @param TermIndex $termIndex Used to search the terms
	 * @param LabelDescriptionLookup $labelDescriptionLookup Provides displayTerms
	 * @param LanguageFallbackChainFactory $fallbackFactory Provides fallback for search languages
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		TermIndex $termIndex,
		LabelDescriptionLookup $labelDescriptionLookup,
		LanguageFallbackChainFactory $fallbackFactory
	) {
		$this->termIndex = $termIndex;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->languageFallbackChainFactory = $fallbackFactory;
	}

	/**
	 * @return int
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * @return bool
	 */
	public function getIsCaseSensitive() {
		return $this->isCaseSensitive;
	}

	/**
	 * @return bool
	 */
	public function getIsPrefixSearch() {
		return $this->isPrefixSearch;
	}

	/**
	 * @param int $limit Hard upper limit of 5000
	 */
	public function setLimit( $limit ) {
		Assert::parameterType( 'integer', $limit, '$limit' );
		Assert::parameter( $limit > 0, '$limit', 'Must be positive' );
		if ( $limit > 5000 ) {
			$limit = 5000;
		}
		$this->limit = $limit;
	}

	/**
	 * @param bool $caseSensitive
	 */
	public function setIsCaseSensitive( $caseSensitive ) {
		Assert::parameterType( 'boolean', $caseSensitive, '$caseSensitive' );
		$this->isCaseSensitive = $caseSensitive;
	}

	/**
	 * @param bool $prefixSearch
	 */
	public function setIsPrefixSearch( $prefixSearch ) {
		Assert::parameterType( 'boolean', $prefixSearch, '$prefixSearch' );
		$this->isPrefixSearch = $prefixSearch;

	}

	/**
	 * @see TermSearchInterface::searchForTerms
	 *
	 * @param string $text
	 * @param string[] $languageCodes
	 * @param string $entityType
	 * @param string[] $termTypes
	 *
	 * @returns array[]
	 */
	public function searchForTerms(
		$text,
		array $languageCodes,
		$entityType,
		array $termTypes
	) {
		$matchedTerms =  $this->termIndex->getMatchingTerms(
			$this->getTermIndexEntrys(
				$text,
				$this->addFallbackLanguageCodes( $languageCodes ),
				$termTypes
			),
			null,
			$entityType,
			$this->getTermIndexOptions()
		);

		return $this->getSearchResults( $matchedTerms );
	}

	/**
	 * @param TermIndexEntry[] $terms
	 *
	 * @returns array[] array of arrays containing the following:
	 *          ['entityId'] => EntityId EntityId
	 *          ['matchedTerm'] => Term MatchedTerm
	 *          ['displayTerms'] => Term[] DisplayTerms
	 */
	private function getSearchResults( array $terms ) {
		$searchResults = array();
		foreach ( $terms as $term ) {
			$searchResults[] = $this->getSearchResult( $term );
		}
		return $searchResults;
	}

	/**
	 * @param TermIndexEntry $term
	 *
	 * @returns array containing the following:
	 *          ['entityId'] => EntityId EntityId
	 *          ['matchedTerm'] => Term MatchedTerm
	 *          ['displayTerms'] => Term[] array with possible keys TermIndexEntry::TYPE_*
	 */
	private function getSearchResult( TermIndexEntry $term ) {
		$entityId = $term->getEntityId();
		$searchResult = array(
			'entityId' => $entityId,
			'matchedTerm' => $this->getTermFromTermIndexEntry( $term ),
			'displayTerms' => $this->getDisplayTerms( $entityId ),
		);
		return $searchResult;
	}

	/**
	 * @param TermIndexEntry $term
	 *
	 * @return Term
	 */
	private function getTermFromTermIndexEntry( TermIndexEntry $term ) {
		return new Term( $term->getLanguage(), $term->getText() );
	}

	private function getTermIndexOptions() {
		return array(
			'caseSensitive' => $this->isCaseSensitive,
			'prefixSearch' => $this->isPrefixSearch,
			'LIMIT' => $this->limit,
		);
	}

	/**
	 * @param array $languageCodes
	 *
	 * @return array
	 */
	private function addFallbackLanguageCodes( array $languageCodes ) {
		$languageCodesWithFallback = array();
		foreach ( $languageCodes as $languageCode ) {
			$fallbackChain = $this->languageFallbackChainFactory->newFromLanguageCode( $languageCode );
			$languageCodesWithFallback = array_merge(
				$languageCodesWithFallback,
				$fallbackChain->getFetchLanguageCodes()
			);
		}

		return array_unique( $languageCodesWithFallback );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return Term[] array with possible keys TermIndexEntry::TYPE_*
	 */
	private function getDisplayTerms( EntityId $entityId ) {
		$displayTerms = array();
		try{
			$displayTerms[TermIndexEntry::TYPE_LABEL] = $this->labelDescriptionLookup->getLabel( $entityId );
		} catch( OutOfBoundsException $e ) {
			// Ignore
		};
		try{
			$displayTerms[TermIndexEntry::TYPE_DESCRIPTION] = $this->labelDescriptionLookup->getDescription( $entityId );
		} catch( OutOfBoundsException $e ) {
			// Ignore
		};
		return $displayTerms;
	}

	/**
	 * @param string $text
	 * @param string[] $languageCodes
	 * @param string[] $termTypes
	 *
	 * @returns TermIndexEntry[]
	 */
	private function getTermIndexEntrys( $text, $languageCodes, $termTypes ) {
		$terms = array();
		foreach ( $languageCodes as $languageCode ) {
			foreach ( $termTypes as $termType ) {
				$terms[] = new TermIndexEntry( array(
					'termText' => $text,
					'termLanguage' => $languageCode,
					'termType' => $termType,
				) );
			}
		}
		return $terms;
	}

}
