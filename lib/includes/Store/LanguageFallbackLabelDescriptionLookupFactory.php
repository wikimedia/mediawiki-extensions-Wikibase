<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\LanguageFallbackChainFactory;

/**
 * Factory to provide an LabelDescriptionLookup which does automatic prefetching
 * of terms, applies a language fallback and returns the LabelDescriptionLookup.
 *
 * @license GPL-2.0-or-later
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
	 * @var int
	 */
	private $fallbackMode;

	/**
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param TermLookup $termLookup
	 * @param TermBuffer|null $termBuffer
	 * @param int $fallbackMode Either 0 or a combination of the
	 *  LanguageFallbackChainFactory::FALLBACK_... constants.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		TermLookup $termLookup,
		TermBuffer $termBuffer = null,
		$fallbackMode = LanguageFallbackChainFactory::FALLBACK_ALL
	) {
		if ( !is_int( $fallbackMode ) ) {
			throw new InvalidArgumentException( '$fallbackMode must be an integer' );
		}

		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->termLookup = $termLookup;
		$this->termBuffer = $termBuffer;
		$this->fallbackMode = $fallbackMode;
	}

	/**
	 * Returns a LabelDescriptionLookup where terms are prefetched for the given
	 * entity ids with a language fallback chain applied for the given language.
	 *
	 * @param Language $language
	 * @param EntityId[] $entityIds Array of entity ids that should be prefetched. Only relevant
	 *  when a TermBuffer was set in the constructor. Default is no prefetching.
	 * @param string[] $termTypes Array with one or more of the types 'label', 'alias' and
	 *  'description'. Default is only 'label'.
	 *
	 * @return LanguageFallbackLabelDescriptionLookup
	 */
	public function newLabelDescriptionLookup(
		Language $language,
		array $entityIds = [],
		array $termTypes = [ 'label' ]
	) {
		$languageFallbackChain = $this->languageFallbackChainFactory->newFromLanguage(
			$language,
			$this->fallbackMode
		);

		$languages = $languageFallbackChain->getFetchLanguageCodes();

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			$this->termLookup,
			$languageFallbackChain
		);

		// Optionally prefetch the terms of the entities passed in here
		// $termLookup and $termBuffer should be the same BufferingTermLookup then
		if ( $this->termBuffer !== null ) {
			$this->termBuffer->prefetchTerms( $entityIds, $termTypes, $languages );
		}

		return $labelDescriptionLookup;
	}

}
