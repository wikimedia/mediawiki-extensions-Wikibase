<?php

namespace Wikibase\Lib\Formatters;

use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LanguageLabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Lib\LanguageFallbackChain;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;

/**
 * Factory for LabelDescriptionLookup objects based on FormatterOptions.
 *
 * The LabelDescriptionLookup is created based on the values of the options
 * OPT_LANGUAGE_FALLBACK_CHAIN, and ValueFormatter::OPT_LANG:
 *
 * * If OPT_LANGUAGE_FALLBACK_CHAIN is set, a LanguageFallbackLabelDescriptionLookup will be created byed on
 *   the LanguageFallbackChain contained in that option.
 * * If ValueFormatter::OPT_LANG is set, a LanguageLabelDescriptionLookup is created
 * * If none of these options is set, an InvalidArgumentException is thrown.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class FormatterLabelDescriptionLookupFactory {

	const OPT_LANGUAGE_FALLBACK_CHAIN = 'languages';

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	public function __construct( TermLookup $termLookup ) {
		$this->termLookup = $termLookup;
	}

	/**
	 * @param FormatterOptions $options
	 *
	 * @throws InvalidArgumentException
	 * @return LabelDescriptionLookup
	 */
	public function getLabelDescriptionLookup( FormatterOptions $options ) {
		if ( $options->hasOption( self::OPT_LANGUAGE_FALLBACK_CHAIN ) ) {
			return $this->newLanguageFallbackLabelDescriptionLookup( $options );
		} elseif ( $options->hasOption( ValueFormatter::OPT_LANG ) ) {
			return $this->newLanguageLabelDescriptionLookup( $options );
		} else {
			throw new InvalidArgumentException( 'OPT_LANG or OPT_LANGUAGE_FALLBACK_CHAIN, '
				. 'must be set in FormatterOptions.' );
		}
	}

	private function newLanguageFallbackLabelDescriptionLookup( FormatterOptions $options ) {
		$fallbackChain = $options->getOption( self::OPT_LANGUAGE_FALLBACK_CHAIN );

		if ( !( $fallbackChain instanceof LanguageFallbackChain ) ) {
			throw new InvalidArgumentException( 'OPT_LANGUAGE_FALLBACK_CHAIN must be used ' .
				'with an instance of LanguageFallbackChain.' );
		}

		return new LanguageFallbackLabelDescriptionLookup( $this->termLookup, $fallbackChain );
	}

	private function newLanguageLabelDescriptionLookup( FormatterOptions $options ) {
		$languageCode = $options->getOption( ValueFormatter::OPT_LANG );

		if ( !is_string( $languageCode ) ) {
			throw new InvalidArgumentException( 'ValueFormatter::OPT_LANG must be a '
				. 'language code string.' );
		}

		return new LanguageLabelDescriptionLookup( $this->termLookup, $languageCode );
	}

}
