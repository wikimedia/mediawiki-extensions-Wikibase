<?php

namespace Wikibase\Repo\Interactors;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LabelDescriptionLookup;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;
use Wikimedia\Assert\Assert;

/**
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class TermSearchInteractor implements TermSearchInterface {

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
	 * @var LanguageFallbackChainFactory|null
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var LabelDescriptionLookup|null
	 */
	private $labelDescriptionLookup;

	/**
	 * @param TermIndex $termIndex
	 * @param bool $caseSensitive
	 * @param bool $prefixSearch
	 * @param int $limit Default to 5000, Hard upper limit of 5000
	 * @param LabelDescriptionLookup|null $labelDescriptionLookup Provides display terms
	 * @param LanguageFallbackChainFactory|null $fallbackFactory Provides fallback for search languages
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		TermIndex $termIndex,
		$caseSensitive,
		$prefixSearch,
		$limit = 5000,
		LabelDescriptionLookup $labelDescriptionLookup = null,
		LanguageFallbackChainFactory $fallbackFactory = null
	) {
		Assert::parameterType( 'boolean', $caseSensitive, '$caseSensitive' );
		Assert::parameterType( 'boolean', $prefixSearch, '$prefixSearch' );
		Assert::parameterType( 'integer', $limit, '$limit' );
		Assert::parameter( $limit > 0, '$limit', 'Must be posotive' );

		$this->termIndex = $termIndex;
		$this->isCaseSensitive = $caseSensitive;
		$this->isPrefixSearch = $prefixSearch;
		if ( $limit > 5000 ) {
			$limit = 5000;
		}
		$this->limit = $limit;
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
	 * @see TermSearchInterface::searchForTerms
	 *
	 * @param string $text
	 * @param string[] $languageCodes
	 * @param string $entityType
	 * @param string[] $termTypes
	 *
	 * @returns array[]
	 */
	public function searchForTerms( $text, array $languageCodes, $entityType, array $termTypes ) {
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
	 *          ['displayTerms'] => Term[]|null DisplayTerms
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
	 *          ['displayTerms'] => Term[]|null array with keys TermIndexEntry::TYPE_* or null
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
		if( $this->languageFallbackChainFactory === null ) {
			return $languageCodes;
		}

		$fallbackMode = (
			LanguageFallbackChainFactory::FALLBACK_VARIANTS
			| LanguageFallbackChainFactory::FALLBACK_OTHERS
			| LanguageFallbackChainFactory::FALLBACK_SELF );

		$languageCodesWithFallback = array();
		foreach ( $languageCodes as $languageCode ) {
			$languageCodesWithFallback[] = $languageCode;
			$fallbackChain = $this->languageFallbackChainFactory
				->newFromLanguageCode( $languageCode, $fallbackMode );
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
	 * @return Term[]|null array with keys TermIndexEntry::TYPE_* or null
	 */
	private function getDisplayTerms( EntityId $entityId ) {
		if ( $this->labelDescriptionLookup === null ) {
			return null;
		}
		return array(
			TermIndexEntry::TYPE_LABEL => $this->labelDescriptionLookup->getLabel( $entityId ),
			TermIndexEntry::TYPE_DESCRIPTION =>$this->labelDescriptionLookup->getDescription( $entityId ),
		);
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
