<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Language;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\LanguageFallbackChainFactory;

/**
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
	 * @var int
	 */
	private $fallbackMode;

	/**
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param TermLookup $termLookup
	 * @param int $fallbackMode Either 0 or a combination of the
	 *  LanguageFallbackChainFactory::FALLBACK_... constants.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		TermLookup $termLookup,
		$fallbackMode = LanguageFallbackChainFactory::FALLBACK_ALL
	) {
		if ( !is_int( $fallbackMode ) ) {
			throw new InvalidArgumentException( '$fallbackMode must be an integer' );
		}

		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->termLookup = $termLookup;
		$this->fallbackMode = $fallbackMode;
	}

	/**
	 * Returns a LabelDescriptionLookup with a language fallback
	 * chain applied for the given language.
	 *
	 * @param Language $language
	 *
	 * @return LanguageFallbackLabelDescriptionLookup
	 */
	public function newLabelDescriptionLookup( Language $language ) {
		$languageFallbackChain = $this->languageFallbackChainFactory->newFromLanguage(
			$language,
			$this->fallbackMode
		);

		$lookup = new FallbackChainLabelDescriptionLookup(
			$this->termLookup,
			$languageFallbackChain
		);

		return new BufferingLanguageFallbackLabelDescriptionLookup( $lookup );
	}

}
