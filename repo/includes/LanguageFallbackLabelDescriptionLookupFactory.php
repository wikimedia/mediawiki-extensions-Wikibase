<?php

namespace Wikibase\Repo;

use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LabelDescriptionLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\TermLookup;
use Wikibase\Store\TermBuffer;

/**
 * Factory to provide an LabelDescriptionLookup which does automatic prefetching
 * of labels, applies a language fallback and returns the LabelDescriptionLookup.
 *
 * @license GPL 2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LanguageFallbackLabelDescriptionLookupFactory {

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	/**
	 * @var TermBuffer|null
	 */
	private $termBuffer;

	/**
	 * @see LanguageFallbackChainFactory::FALLBACK_
	 * @var int
	 */
	private $fallbackMode;

	public function __construct(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		TermLookup $termLookup,
		TermBuffer $termBuffer = null
	) {
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->termLookup = $termLookup;
		$this->termBuffer = $termBuffer;

		$this->fallbackMode = LanguageFallbackChainFactory::FALLBACK_SELF
			| LanguageFallbackChainFactory::FALLBACK_VARIANTS
			| LanguageFallbackChainFactory::FALLBACK_OTHERS;
	}

	/**
	 * @var int $fallbackMode
	 */
	public function setFallbackMode( $fallbackMode ) {
		$this->fallbackMode = $fallbackMode;
	}

	/**
	 * Returns a LabelDescriptionLookupfor the given list of entity ids
	 * with a language fallback chain applied for the given language.
	 *
	 * @param Language $language
	 * @param EntityId[] $entityIds
	 *
	 * @return LabelDescriptionLookup
	 */
	public function newLabelDescriptionLookup( Language $language, array $entityIds ) {
		$languageFallbackChain = $this->languageFallbackChainFactory->newFromLanguage(
			$language,
			$this->fallbackMode
		);

		$languages = $languageFallbackChain->getFetchLanguageCodes();

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			$this->termLookup,
			$languageFallbackChain
		);

		if ( $this->termBuffer !== null ) {
			$this->termBuffer->prefetchTerms( $entityIds, array( 'label' ), $languages );
		}

		return $labelDescriptionLookup;
	}

}

