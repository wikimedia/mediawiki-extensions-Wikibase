<?php

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;

/**
 * LanguageFallbackLabelDescriptionLookup decorator that records label usage in an UsageAccumulator.
 *
 * @see UsageAccumulator
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class UsageTrackingLanguageFallbackLabelDescriptionLookup implements LabelDescriptionLookup {

	/**
	 * @var LanguageFallbackLabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @var LanguageFallbackChain
	 */
	private $languageFallbackChain;

	/**
	 * @param LanguageFallbackLabelDescriptionLookup $labelDescriptionLookup
	 * @param UsageAccumulator $usageAccumulator
	 * @param LanguageFallbackChain $languageFallbackChain
	 */
	public function __construct(
		LanguageFallbackLabelDescriptionLookup $labelDescriptionLookup,
		UsageAccumulator $usageAccumulator,
		LanguageFallbackChain $languageFallbackChain
	) {
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->usageAccumulator = $usageAccumulator;
		$this->languageFallbackChain = $languageFallbackChain;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getLabel( EntityId $entityId ) {
		$termFallback = $this->labelDescriptionLookup->getLabel( $entityId );

		$this->recordLabelUsages( $entityId, $termFallback );

		return $termFallback;
	}

	/**
	 * Record the appropriate label usages for a given TermFallback.
	 *
	 * @param EntityId $entityId
	 * @param TermFallback|null $termFallback
	 */
	private function recordLabelUsages( EntityId $entityId, TermFallback $termFallback = null ) {
		$fetchLanguages = $this->languageFallbackChain->getFetchLanguageCodes();

		if ( $termFallback === null ) {
			// Nothing found: Record the full fallback chains as used.
			$languagesTouched = $fetchLanguages;
		} else {
			// Found something: Find out which language it was originally in
			$languageUsed = $termFallback->getSourceLanguageCode();
			if ( !$languageUsed ) {
				$languageUsed = $termFallback->getActualLanguageCode();
			}

			// Record the relevant part of the fallback chain as used.
			$languagesTouched = array_slice(
				$fetchLanguages,
				0,
				array_search( $languageUsed, $fetchLanguages ) + 1
			);
		}

		foreach ( $languagesTouched as $lang ) {
			$this->usageAccumulator->addLabelUsage( $entityId, $lang );
		}
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getDescription( EntityId $entityId ) {
		return $this->labelDescriptionLookup->getDescription( $entityId );
	}

}
