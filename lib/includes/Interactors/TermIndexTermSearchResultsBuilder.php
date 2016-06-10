<?php

namespace Wikibase\Lib\Interactors;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Store\BufferingTermLookup;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;
use Wikimedia\Assert\Assert;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class TermIndexTermSearchResultsBuilder {

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

    /**
     * @var BufferingTermLookup
     */
    private $bufferingTermLookup;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var string languageCode to use for display terms
	 */
	private $displayLanguageCode;

	/**
	 * @param LanguageFallbackChainFactory $fallbackFactory
	 * @param BufferingTermLookup $bufferingTermLookup Provides the displayTerms
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param string $displayLanguageCode
	 */
	public function __construct(
		LanguageFallbackChainFactory $fallbackFactory,
		BufferingTermLookup $bufferingTermLookup,
		LabelDescriptionLookup $labelDescriptionLookup,
		$displayLanguageCode
	) {
		Assert::parameterType( 'string', $displayLanguageCode, '$displayLanguageCode' );
		$this->languageFallbackChainFactory = $fallbackFactory;
		$this->bufferingTermLookup = $bufferingTermLookup;
		$this->displayLanguageCode = $displayLanguageCode;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
	}

	/**
	 * @param TermIndexEntry[] $termIndexEntries
	 *
	 * @return TermSearchResult[]
	 */
	public function getTermSearchResults( array $termIndexEntries ) {
		$entityIds = $this->getEntityIdsForTermIndexEntries( $termIndexEntries );
		$this->preFetchLabelsAndDescriptionsForDisplay( $entityIds );

		$searchResults = array();
		foreach ( $termIndexEntries as $termIndexEntry ) {
			$searchResults[] = $this->convertToSearchResult( $termIndexEntry );
		}
		return array_values( $searchResults );
	}

	/**
	 * @param TermIndexEntry[] $termsIndexEntries
	 *
	 * @return EntityId[]
	 */
	private function getEntityIdsForTermIndexEntries( array $termsIndexEntries ) {
		$entityIds = array();
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
     * @param EntityId[] $entityIds
     */
    private function preFetchLabelsAndDescriptionsForDisplay( array $entityIds ) {
        $this->bufferingTermLookup->prefetchTerms(
            $entityIds,
            array( TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_DESCRIPTION ),
            $this->addFallbackLanguageCodes( array( $this->displayLanguageCode ) )
        );
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
			$termIndexEntry->getType(),
			$entityId,
			$this->getLabelDisplayTerm( $entityId ),
			$this->getDescriptionDisplayTerm( $entityId )
		);
	}

	/**
	 * @param string[] $languageCodes
	 *
	 * @return string[]
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

}
