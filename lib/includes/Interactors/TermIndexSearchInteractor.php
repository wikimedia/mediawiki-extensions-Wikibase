<?php

namespace Wikibase\Lib\Interactors;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Katie Filbert < aude.wiki@gmail.com >
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
	 * @var TermIndexTermSearchResultsBuilder
	 */
	private $termSearchResultsBuilder;

	/**
	 * @var TermSearchOptions
	 */
	private $termSearchOptions;

	/**
	 * @param TermIndex $termIndex Used to search the terms
	 * @param LanguageFallbackChainFactory $fallbackFactory
	 * @param TermIndexTermSearchResultsBuilder $termSearchResultsBuilder
	 */
	public function __construct(
		TermIndex $termIndex,
		LanguageFallbackChainFactory $fallbackFactory,
		TermIndexTermSearchResultsBuilder $termSearchResultsBuilder
	) {
		$this->termIndex = $termIndex;
		$this->languageFallbackChainFactory = $fallbackFactory;
		$this->termSearchResultsBuilder = $termSearchResultsBuilder;

		$this->termSearchOptions = new TermSearchOptions();
	}

	/**
	 * @param TermSearchOptions $termSearchOptions
	 */
	public function setTermSearchOptions( TermSearchOptions $termSearchOptions ) {
		$this->termSearchOptions = $termSearchOptions;
	}

	/**
	 * @param int $limit Hard upper limit of 5000
	 * @deprecated
	 */
	public function setLimit( $limit ) {
		$this->termSearchOptions->setLimit( $limit );
	}

	/**
	 * @param bool $caseSensitive
	 * @deprecated
	 */
	public function setIsCaseSensitive( $caseSensitive ) {
		$this->termSearchOptions->setIsCaseSensitive( $caseSensitive );
	}

	/**
	 * @param bool $prefixSearch
	 * @deprecated
	 */
	public function setIsPrefixSearch( $prefixSearch ) {
		$this->termSearchOptions->setIsPrefixSearch( $prefixSearch );
	}

	/**
	 * @param bool $useLanguageFallback
	 * @deprecated
	 */
	public function setUseLanguageFallback( $useLanguageFallback ) {
		$this->termSearchOptions->setUseLanguageFallback( $useLanguageFallback );
	}

	/**
	 * @see TermSearchInteractor::searchForEntities
	 *
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param string[] $termTypes
	 *
	 * @return TermSearchResult[]
	 */
	public function searchForEntities(
		$text,
		$languageCode,
		$entityType,
		array $termTypes
	) {
		$matchedTermIndexEntries = $this->getMatchingTermIndexEntries(
			$text,
			$languageCode,
			$entityType,
			$termTypes
		);

		return $this->termSearchResultsBuilder->getTermSearchResults( $matchedTermIndexEntries );
	}

	/**
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param string[] $termTypes
	 *
	 * @return TermIndexEntry[]
	 */
	private function getMatchingTermIndexEntries(
		$text,
		$languageCode,
		$entityType,
		array $termTypes
	) {
		$languageCodes = [ $languageCode ];

		$matchedTermIndexEntries = $this->termIndex->getTopMatchingTerms(
			$this->makeTermIndexEntryTemplates(
				$text,
				$languageCodes,
				$termTypes
			),
			null,
			$entityType,
			$this->getTermIndexOptions( $this->termSearchOptions )
		);

		$limit = $this->termSearchOptions->getLimit();

		// Shortcut out if we already have enough TermIndexEntries
		if ( count( $matchedTermIndexEntries ) >= $limit
			|| !$this->termSearchOptions->getUseLanguageFallback()
		) {
			return $matchedTermIndexEntries;
		}

		if ( $this->termSearchOptions->getUseLanguageFallback() ) {
			// Matches in the main language will always be first
			$matchedTermIndexEntries = array_merge(
				$matchedTermIndexEntries,
				$this->getFallbackMatchedTermIndexEntries(
					$text,
					$languageCodes,
					$termTypes,
					$entityType,
					$this->getMatchedEntityIdSerializations( $matchedTermIndexEntries )
				)
			);

			if ( count( $matchedTermIndexEntries ) > $limit ) {
				array_slice( $matchedTermIndexEntries, 0, $limit, true );
			}
		}

		return $matchedTermIndexEntries;
	}

	/**
	 * @param TermIndexEntry[]
	 *
	 * @return string[]
	 */
	private function getMatchedEntityIdSerializations( array $matchedTermIndexEntries ) {
		$matchedEntityIdSerializations = [];

		foreach ( $matchedTermIndexEntries as $termIndexEntry ) {
			$matchedEntityIdSerializations[] = $termIndexEntry->getEntityId()->getSerialization();
		}

		return $matchedEntityIdSerializations;
	}

	/**
	 * @param string $text
	 * @param string[] $languageCodes
	 * @param string[] $termTypes
	 * @param string $entityType
	 * @param string[] $matchedEntityIdSerializations
	 */
	private function getFallbackMatchedTermIndexEntries(
		$text,
		array $languageCodes,
		$termTypes,
		$entityType,
		array $matchedEntityIdSerializations
	) {
		$fallbackMatchedTermIndexEntries = $this->termIndex->getTopMatchingTerms(
			$this->makeTermIndexEntryTemplates(
				$text,
				$this->addFallbackLanguageCodes( $languageCodes ),
				$termTypes
			),
			null,
			$entityType,
			$this->getTermIndexOptions()
		);

		// Remove any IndexEntries that are already have an match for
		foreach ( $fallbackMatchedTermIndexEntries as $key => $termIndexEntry ) {
			if ( in_array(
				$termIndexEntry->getEntityId()->getSerialization(),
				$matchedEntityIdSerializations
			) ) {
				unset( $fallbackMatchedTermIndexEntries[$key] );
			}
		}

		return $fallbackMatchedTermIndexEntries;
	}

	private function getTermIndexOptions() {
		return [
			'caseSensitive' => $this->termSearchOptions->getIsCaseSensitive(),
			'prefixSearch' => $this->termSearchOptions->getIsPrefixSearch(),
			'LIMIT' => $this->termSearchOptions->getLimit(),
		];
	}

	/**
	 * @param string[] $languageCodes
	 *
	 * @return string[]
	 */
	private function addFallbackLanguageCodes( array $languageCodes ) {
		$languageCodesWithFallback = [];

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
	 * @param string $text
	 * @param string[] $languageCodes
	 * @param string[] $termTypes
	 *
	 * @return TermIndexEntry[]
	 */
	private function makeTermIndexEntryTemplates( $text, array $languageCodes, array $termTypes ) {
		$terms = [];

		foreach ( $languageCodes as $languageCode ) {
			foreach ( $termTypes as $termType ) {
				$terms[] = new TermIndexEntry( [
					'termText' => $text,
					'termLanguage' => $languageCode,
					'termType' => $termType,
				] );
			}
		}

		return $terms;
	}

}
