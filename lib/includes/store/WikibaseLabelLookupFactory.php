<?php

namespace Wikibase\Lib\Store;

use Wikibase\LanguageFallbackChain;

/**
 * A LabelLookupFactory
 *
 * @license GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
class WikibaseLabelLookupFactory implements LabelLookupFactory {

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	/**
	 * @param TermLookup $termLookup
	 */
	public function __construct( TermLookup $termLookup ) {
		$this->termLookup = $termLookup;
	}

	/**
	 * @param {LanguageFallbackChain|string} $languageSpec
	 *
	 * @return LabelLookup
	 */
	public function getLabelLookup( $languageSpec ) {
		if ( $languageSpec instanceof LanguageFallbackChain ) {
			$labelLookup = new LanguageFallbackLabelLookup( $this->termLookup, $languageSpec );
		} else {
			$labelLookup = new LanguageLabelLookup( $this->termLookup, $languageSpec );
		}

		return $labelLookup;
	}

}
