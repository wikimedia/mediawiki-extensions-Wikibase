<?php declare( strict_types=1 );

namespace Wikibase\Lib\Interactors;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\TermIndexEntry;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class MatchingTermsLookupSearchInteractor implements ConfigurableTermSearchInteractor {

	private MatchingTermsLookup $matchingTermsLookup;
	private LanguageFallbackChainFactory $languageFallbackChainFactory;
	private PrefetchingTermLookup $bufferingTermLookup;
	private string $displayLanguageCode;
	private LanguageFallbackLabelDescriptionLookup $labelDescriptionLookup;
	private TermSearchOptions $termSearchOptions;

	/**
	 * @param MatchingTermsLookup $matchingTermsLookup Used to search the terms
	 * @param LanguageFallbackChainFactory $fallbackFactory
	 * @param PrefetchingTermLookup $bufferingTermLookup Provides the displayTerms
	 * @param string $displayLanguageCode languageCode to use for display terms
	 */
	public function __construct(
		MatchingTermsLookup $matchingTermsLookup,
		LanguageFallbackChainFactory $fallbackFactory,
		PrefetchingTermLookup $bufferingTermLookup,
		string $displayLanguageCode
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

	public function setTermSearchOptions( TermSearchOptions $termSearchOptions ): void {
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
		string $text,
		string $languageCode,
		string $entityType,
		array $termTypes
	): array {
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
		string $text,
		string $languageCode,
		string $entityType,
		array $termTypes
	): array {
		$matchedTermIndexEntries = $this->matchingTermsLookup->getMatchingTerms(
			$text,
			$entityType,
			$languageCode,
			$termTypes,
			$this->getTermIndexOptions()
		);

		$limit = $this->termSearchOptions->getLimit();

		if ( count( $matchedTermIndexEntries ) < $limit && $this->termSearchOptions->getUseLanguageFallback() ) {
			// Matches in the main language will always be first
			$matchedTermIndexEntries = array_merge(
				$matchedTermIndexEntries,
				$this->getFallbackMatchedTermIndexEntries(
					$text,
					$languageCode,
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
	private function getMatchedEntityIdSerializations( array $matchedTermIndexEntries ): array {
		$matchedEntityIdSerializations = [];

		foreach ( $matchedTermIndexEntries as $termIndexEntry ) {
			$matchedEntityIdSerializations[] = $termIndexEntry->getEntityId()->getSerialization();
		}

		return $matchedEntityIdSerializations;
	}

	/**
	 * @param string $text
	 * @param string $languageCode
	 * @param string[] $termTypes
	 * @param string $entityType
	 * @param string[] $matchedEntityIdSerializations
	 *
	 * @return TermIndexEntry[]
	 */
	private function getFallbackMatchedTermIndexEntries(
		string $text,
		string $languageCode,
		array $termTypes,
		string $entityType,
		array $matchedEntityIdSerializations
	): array {
		$fallbackMatchedTermIndexEntries = $this->matchingTermsLookup->getMatchingTerms(
			$text,
			$entityType,
			$this->getFallbackLanguageCodes( $languageCode ),
			$termTypes,
			$this->getTermIndexOptions()
		);

		// Remove any IndexEntries that there is already a match for
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
	private function getSearchResults( array $termIndexEntries ): array {
		$searchResults = [];
		foreach ( $termIndexEntries as $termIndexEntry ) {
			$searchResults[] = $this->convertToSearchResult( $termIndexEntry );
		}
		return array_values( $searchResults );
	}

	/**
	 * @param EntityId[] $entityIds
	 */
	private function preFetchLabelsAndDescriptionsForDisplay( array $entityIds ): void {
		$this->bufferingTermLookup->prefetchTerms(
			$entityIds,
			[ TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_DESCRIPTION ],
			$this->getFallbackLanguageCodes( $this->displayLanguageCode )
		);
	}

	/**
	 * @param TermIndexEntry[] $termsIndexEntries
	 *
	 * @return EntityId[]
	 */
	private function getEntityIdsForTermIndexEntries( array $termsIndexEntries ): array {
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

	private function convertToSearchResult( TermIndexEntry $termIndexEntry ): TermSearchResult {
		$entityId = $termIndexEntry->getEntityId();
		return new TermSearchResult(
			$termIndexEntry->getTerm(),
			$termIndexEntry->getTermType(),
			$entityId,
			$this->getLabelDisplayTerm( $entityId ),
			$this->getDescriptionDisplayTerm( $entityId )
		);
	}

	private function getTermIndexOptions(): array {
		return [
			'caseSensitive' => $this->termSearchOptions->getIsCaseSensitive(),
			'prefixSearch' => $this->termSearchOptions->getIsPrefixSearch(),
			'LIMIT' => $this->termSearchOptions->getLimit(),
		];
	}

	/**
	 * @param string $languageCode
	 *
	 * @return string[]
	 */
	private function getFallbackLanguageCodes( string $languageCode ): array {
		return $this->languageFallbackChainFactory->newFromLanguageCode( $languageCode )->getFetchLanguageCodes();
	}

	private function getLabelDisplayTerm( EntityId $entityId ): ?Term {
		return $this->labelDescriptionLookup->getLabel( $entityId );
	}

	private function getDescriptionDisplayTerm( EntityId $entityId ): ?Term {
		return $this->labelDescriptionLookup->getDescription( $entityId );
	}

}
