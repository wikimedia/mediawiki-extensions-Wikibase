<?php

namespace Wikibase\Repo\Interactors;

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
	 * @var string languageCode to use for display terms
	 */
	private $displayLanguageCode;

	/**
	 * @var bool do a case sensitive search
	 */
	private $isCaseSensitive = false;

	/**
	 * @var bool do a prefix search
	 */
	private $isPrefixSearch = false;

	/**
	 * @var bool use language fallback in the search
	 */
	private $useLanguageFallback = true;

	/**
	 * @var int
	 */
	private $limit = 5000;

	/**
	 * @param TermIndex $termIndex Used to search the terms
	 * @param LanguageFallbackChainFactory $fallbackFactory Provides fallback for search languages
	 * @param LabelDescriptionLookup $labelDescriptionLookup Provides displayTerms
	 * @param string $displayLanguageCode should be the same codd as used in the LabelDescriptionLookup
	 */
	public function __construct(
		TermIndex $termIndex,
		LanguageFallbackChainFactory $fallbackFactory,
		LabelDescriptionLookup $labelDescriptionLookup,
		$displayLanguageCode
	) {
		Assert::parameterType( 'string', $displayLanguageCode, '$displayLanguageCode' );
		$this->termIndex = $termIndex;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->languageFallbackChainFactory = $fallbackFactory;
		$this->displayLanguageCode = $displayLanguageCode;
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
	 * @return bool
	 */
	public function getUseLanguageFallback() {
		return $this->useLanguageFallback;
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
	 * @param bool $useLanguageFallback
	 */
	public function setUseLanguageFallback( $useLanguageFallback ) {
		Assert::parameterType( 'boolean', $useLanguageFallback, '$useLanguageFallback' );
		$this->useLanguageFallback = $useLanguageFallback;
	}

	/**
	 * @see TermSearchInteractor::searchForTerms
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
		if( $this->useLanguageFallback ) {
			$languageCodes = $this->addFallbackLanguageCodes( $languageCodes );
		}

		$termIndexEntries =  $this->termIndex->getMatchingTerms(
			$this->getTermIndexEntrys(
				$text,
				$languageCodes,
				$termTypes
			),
			null,
			$entityType,
			$this->getTermIndexOptions()
		);

		return $this->getSearchResults( $termIndexEntries );
	}

	/**
	 * @param TermIndexEntry[] $termsIndexEntries
	 *
	 * @returns array[] array of arrays containing the following:
	 *          ['entityId'] => EntityId EntityId
	 *          ['matchedTerm'] => Term MatchedTerm
	 *          ['displayTerms'] => Term[] DisplayTerms
	 */
	private function getSearchResults( array $termsIndexEntries ) {
		$searchResults = array();
		foreach ( $termsIndexEntries as $termIndexEntry ) {
			$searchResults[] = $this->getSearchResult( $termIndexEntry );
		}
		return $searchResults;
	}

	/**
	 * @param TermIndexEntry $termIndexEntry
	 *
	 * @returns array containing the following:
	 *          ['entityId'] => EntityId EntityId
	 *          ['matchedTerm'] => Term MatchedTerm
	 *          ['displayTerms'] => Term[] array with possible keys TermIndexEntry::TYPE_*
	 */
	private function getSearchResult( TermIndexEntry $termIndexEntry ) {
		$entityId = $termIndexEntry->getEntityId();
		$searchResult = array(
			'entityId' => $entityId,
			'matchedTerm' => $this->getTermFromTermIndexEntry( $termIndexEntry ),
			'displayTerms' => $this->getDisplayTerms( $entityId ),
		);
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
	 *
	 * @todo reuse this code (used in SerachEntities and TermIndexSearchInteractor)
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
		$aliasTerms = $this->getTermsFromTermIndexEntries(
			$this->termIndex->getTermsOfEntity(
				$entityId,
				array( TermIndexEntry::TYPE_ALIAS ),
				array( $this->displayLanguageCode )
			)
		);
		if( !empty( $aliasTerms ) ) {
			$displayTerms[TermIndexEntry::TYPE_ALIAS] = $aliasTerms;
		}
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

	/**
	 * @param TermIndexEntry $termIndexEntry
	 *
	 * @return Term
	 *
	 * @todo reuse this code (used in SerachEntities and TermIndexSearchInteractor)
	 */
	private function getTermFromTermIndexEntry( TermIndexEntry $termIndexEntry ) {
		return new Term( $termIndexEntry->getLanguage(), $termIndexEntry->getText() );
	}

	/**
	 * @param TermIndexEntry[] $termIndexEntries
	 *
	 * @return Term[]
	 *
	 * @todo reuse this code (used in SerachEntities and TermIndexSearchInteractor)
	 */
	private function getTermsFromTermIndexEntries( array $termIndexEntries ) {
		$terms = array();
		foreach( $termIndexEntries as $indexEntry ) {
			$terms[] = $this->getTermFromTermIndexEntry( $indexEntry );
		}
		return $terms;
	}

}
