<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use Wikibase\LanguageFallbackChain;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
class PresetLabelLookupFactory implements LabelLookupFactory {

	/**
	 * @var LabelLookup
	 */
	private $labelLookup;

	private $forLanguageSpec;

	/**
	 * @param {LanguageFallbackChain|string} $forLanguageSpec
	 * @param LabelLookup $labelLookup
	 */
	public function __construct( $forLanguageSpec, LabelLookup $labelLookup ) {
		$this->labelLookup = $labelLookup;
		$this->forLanguageSpec = $forLanguageSpec;
	}

	/**
	 * @param {LanguageFallbackChain|string} $languageSpec
	 *
	 * @throws InvalidArgumentException
	 * @return LabelLookup
	 */
	public function getLabelLookup( $languageSpec ) {
		if( !$this->languageSpecsEqual( $languageSpec, $this->forLanguageSpec ) ) {
			throw new InvalidArgumentException(
				'This PresetLabelLookupFactory has no LabelLookup for given language spec'
			);
		}
		return $this->labelLookup;
	}

	private function languageSpecsEqual( $languageSpec1, $languageSpec2 ) {
		if( $languageSpec1 === $languageSpec2 ) {
			return true;
		}

		$l1isFallback = $languageSpec1 instanceof LanguageFallbackChain;
		$l2isFallback = $languageSpec2 instanceof LanguageFallbackChain;

		if( !$l1isFallback || !$l2isFallback ) {
			return false;
		}

		return $languageSpec1->getFallbackChain() == $languageSpec2->getFallbackChain();
	}
}
