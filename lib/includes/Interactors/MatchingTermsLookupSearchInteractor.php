<?php

namespace Wikibase\Lib\Interactors;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\Lib\TermIndexEntry;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class MatchingTermsLookupSearchInteractor implements ConfigurableTermSearchInteractor {

	/**
	 * @var MatchingTermsLookup
	 */
	private $matchingTermsLookup;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var PrefetchingTermLookup
	 */
	private $bufferingTermLookup;

	/**
	 * @var LanguageFallbackLabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var string languageCode to use for display terms
	 */
	private $displayLanguageCode;

	/**
	 * @var TermSearchOptions
	 */
	private $termSearchOptions;

	/**
	 * @param MatchingTermsLookup $matchingTermsLookup Used to search the terms
	 * @param LanguageFallbackChainFactory $fallbackFactory
	 * @param PrefetchingTermLookup $bufferingTermLookup Provides the displayTerms
	 * @param string $displayLanguageCode
	 */
	public function __construct(
		MatchingTermsLookup $matchingTermsLookup,
		LanguageFallbackChainFactory $fallbackFactory,
		PrefetchingTermLookup $bufferingTermLookup,
		$displayLanguageCode
	) {
		Assert::parameterType( 'string', $displayLanguageCode, '$displayLanguageCode' );
		$this->matchingTermsLookup = $matchingTermsLookup;
		$this->bufferingTermLookup = $bufferingTermLookup;
		$this->languageFallbackChainFactory = $fallbackFactory;
		$this->displayLanguageCode = $displayLanguageCode;
		$this->labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			$this->bufferingTermLookup,
			$this->languageFallbackChainFactory->newFromLanguageCode( $this->displayLanguageCode )
		);

		$this->termSearchOptions = new TermSearchOptions();
	}

	public function setTermSearchOptions( TermSearchOptions $termSearchOptions ) {
		$this->termSearchOptions = $termSearchOptions;
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
		$entityIds = $this->getEntityIdsForTermIndexEntries( $matchedTermIndexEntries );

		$this->preFetchLabelsAndDescriptionsForDisplay( $entityIds );
		return $this->getSearchResults( $matchedTermIndexEntries );
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

		$matchedTermIndexEntries = $this->matchingTermsLookup->getMatchingTerms(
			$this->makeTermIndexSearchCriteria(
				$text,
				$languageCodes,
				$termTypes
			),
			null,
			$entityType,
			$this->getTermIndexOptions()
		);

		$limit = $this->termSearchOptions->getLimit();

		if ( count( $matchedTermIndexEntries ) < $limit && $this->termSearchOptions->getUseLanguageFallback() ) {
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
				$matchedTermIndexEntries = array_slice( $matchedTermIndexEntries, 0, $limit, true );
			}
		}

		return $matchedTermIndexEntries;
	}

	/**
	 * @param TermIndexEntry[] $matchedTermIndexEntries
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
	 *
	 * @return TermIndexEntry[]
	 */
	private function getFallbackMatchedTermIndexEntries(
		$text,
		array $languageCodes,
		$termTypes,
		$entityType,
		array $matchedEntityIdSerializations
	) {
		$fallbackMatchedTermIndexEntries = $this->matchingTermsLookup->getMatchingTerms(
			$this->makeTermIndexSearchCriteria(
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

	/**
	 * @param TermIndexEntry[] $termIndexEntries
	 *
	 * @return TermSearchResult[]
	 */
	private function getSearchResults( array $termIndexEntries ) {
		$searchResults = [];
		foreach ( $termIndexEntries as $termIndexEntry ) {
			$searchResults[] = $this->convertToSearchResult( $termIndexEntry );
		}
		return array_values( $searchResults );
	}

	/**
	 * @param EntityId[] $entityIds
	 */
	private function preFetchLabelsAndDescriptionsForDisplay( array $entityIds ) {
		$this->bufferingTermLookup->prefetchTerms(
			$entityIds,
			[ TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_DESCRIPTION ],
			$this->addFallbackLanguageCodes( [ $this->displayLanguageCode ] )
		);
	}

	/**
	 * @param TermIndexEntry[] $termsIndexEntries
	 *
	 * @return EntityId[]
	 */
	private function getEntityIdsForTermIndexEntries( array $termsIndexEntries ) {
		$entityIds = [];
		foreach ( $termsIndexEntries as $termIndexEntry ) {
			$entityId = $termIndexEntry->getEntityId();
			// We would hope that this would never happen, but is possible
			if ( $entityId !== null ) {
				// Use a key so that the array will end up being full of unique IDs
				$entityIds[$entityId->getSerialization()] = $entityId;
			}
		}
		return $entityIds;
	}

	/**
	 * @param TermIndexEntry $termIndexEntry
	 *
	 * @return TermSearchResult
	 */
	private function convertToSearchResult( TermIndexEntry $termIndexEntry ) {
		$entityId = $termIndexEntry->getEntityId();
		return new TermSearchResult(
			$termIndexEntry->getTerm(),
			$termIndexEntry->getTermType(),
			$entityId,
			$this->getLabelDisplayTerm( $entityId ),
			$this->getDescriptionDisplayTerm( $entityId )
		);
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
	 * @param EntityId $entityId
	 *
	 * @return null|Term
	 */
	private function getLabelDisplayTerm( EntityId $entityId ) {
		return $this->labelDescriptionLookup->getLabel( $entityId );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return null|Term
	 */
	private function getDescriptionDisplayTerm( EntityId $entityId ) {
		return $this->labelDescriptionLookup->getDescription( $entityId );
	}

	/**
	 * @param string $text
	 * @param string[] $languageCodes
	 * @param string[] $termTypes
	 *
	 * @return TermIndexSearchCriteria[]
	 */
	private function makeTermIndexSearchCriteria( $text, array $languageCodes, array $termTypes ) {
		$terms = [];
		foreach ( $languageCodes as $languageCode ) {
			foreach ( $termTypes as $termType ) {
				$terms[] = new TermIndexSearchCriteria( [
					'termText' => $text,
					'termLanguage' => $languageCode,
					'termType' => $termType,
				] );
			}
		}
		return $terms;
	}

}
