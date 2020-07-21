<?php

namespace Wikibase\Client\Usage;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * LanguageFallbackLabelDescriptionLookup decorator that records label usage in an UsageAccumulator.
 *
 * @see UsageAccumulator
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class UsageTrackingLanguageFallbackLabelDescriptionLookup implements FallbackLabelDescriptionLookup {

	/**
	 * @var FallbackLabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @var TermLanguageFallbackChain
	 */
	private $termLanguageFallbackChain;

	/**
	 * @var bool
	 */
	private $trackUsagesInAllLanguages;

	/**
	 * @param FallbackLabelDescriptionLookup $labelDescriptionLookup
	 * @param UsageAccumulator $usageAccumulator
	 * @param TermLanguageFallbackChain $termLanguageFallbackChain
	 * @param bool $trackUsagesInAllLanguages
	 */
	public function __construct(
		FallbackLabelDescriptionLookup $labelDescriptionLookup,
		UsageAccumulator $usageAccumulator,
		TermLanguageFallbackChain $termLanguageFallbackChain,
		$trackUsagesInAllLanguages
	) {
		if ( !is_bool( $trackUsagesInAllLanguages ) ) {
			throw new InvalidArgumentException( '$trackUsagesInAllLanguages must be a bool' );
		}

		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->usageAccumulator = $usageAccumulator;
		$this->termLanguageFallbackChain = $termLanguageFallbackChain;
		$this->trackUsagesInAllLanguages = $trackUsagesInAllLanguages;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getLabel( EntityId $entityId ) {
		$termFallback = $this->labelDescriptionLookup->getLabel( $entityId );

		foreach ( $this->getTouchedLanguages( $termFallback ) as $lang ) {
			$this->usageAccumulator->addLabelUsage( $entityId, $lang );
		}

		return $termFallback;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getDescription( EntityId $entityId ) {
		$termFallback = $this->labelDescriptionLookup->getDescription( $entityId );

		foreach ( $this->getTouchedLanguages( $termFallback ) as $lang ) {
			$this->usageAccumulator->addDescriptionUsage( $entityId, $lang );
		}

		return $termFallback;
	}

	/**
	 * Get the languages from the TermLanguageFallbackChain used to get a given TermFallback.
	 *
	 * @param TermFallback|null $termFallback
	 *
	 * @return string[]|null[]
	 */
	private function getTouchedLanguages( TermFallback $termFallback = null ) {
		if ( $this->trackUsagesInAllLanguages ) {
			// On multi-lingual wikis where users can request pages in any language, we can not
			// optimize for one language fallback chain only. Since all possible language fallback
			// chains must cover all languages, we can simply track an "all languages" usage.
			return [ null ];
		}

		$fetchLanguages = $this->termLanguageFallbackChain->getFetchLanguageCodes();

		if ( $termFallback === null ) {
			// Nothing found: Record the full fallback chains as used.
			return $fetchLanguages;
		}

		// Found something: Find out which language it was originally in
		$languageUsed = $termFallback->getSourceLanguageCode();
		if ( !$languageUsed ) {
			$languageUsed = $termFallback->getActualLanguageCode();
		}

		// Record the relevant part of the fallback chain as used.
		return array_slice(
			$fetchLanguages,
			0,
			array_search( $languageUsed, $fetchLanguages ) + 1
		);
	}

}
