<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
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
	 * @throws InvalidArgumentException
	 * @return LabelLookup
	 */
	public function getLabelLookup( $languageSpec ) {
		if ( $languageSpec instanceof LanguageFallbackChain ) {
			$labelLookup = new LanguageFallbackLabelLookup( $this->termLookup, $languageSpec );
		} elseif( is_string( $languageSpec ) ) {
			$labelLookup = new LanguageLabelLookup( $this->termLookup, $languageSpec );
		} else {
			throw new InvalidArgumentException(
				'$languageSpec has to be a string or a LanguageFallbackChain'
			);
		}

		return $labelLookup;
	}

}
