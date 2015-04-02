<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\TermLookup;
use Wikibase\Lib\Store\LabelDescriptionLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\LanguageLabelDescriptionLookup;

/**
 * Factory for LabelDescriptionLookup objects based on FormatterOptions.
 *
 * The LabelDescriptionLookup is created based on the values of the options
 * 'LabelDescriptionLookup', 'languages', and ValueFormatter::OPT_LANG:
 *
 * * 'LabelDescriptionLookup' can be used to provide a custom LabelDescriptionLookup instance directly
 * * If 'languages' is set, a LanguageFallbackLabelDescriptionLookup will be created byed on
 *   the LanguageFallbackChain contained in that option.
 * * If ValueFormatter::OPT_LANG is set, a LanguageLabelDescriptionLookup is created
 * * If none of these options is set, an InvalidArgumentException is thrown.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class FormatterLabelDescriptionLookupFactory {

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
		if ( $options->hasOption( 'LabelDescriptionLookup' ) ) {
			return $this->getLabelDescriptionLookupFromOptions( $options );
		} elseif ( $options->hasOption( 'languages' ) ) {
			return $this->newLanguageFallbackLabelDescriptionLookup( $options );
		} elseif ( $options->hasOption( ValueFormatter::OPT_LANG ) ) {
			return $this->newLanguageLabelDescriptionLookup( $options );
		} else {
			throw new InvalidArgumentException( 'OPT_LANG, languages (fallback chain), '
				. 'or LabelDescriptionLookup must be set in FormatterOptions.' );
		}
	}

	private function getLabelDescriptionLookupFromOptions( FormatterOptions $options ) {
		$labelDescriptionLookup = $options->getOption( 'LabelDescriptionLookup' );

		if ( !( $labelDescriptionLookup instanceof LabelDescriptionLookup ) ) {
			throw new InvalidArgumentException( 'Option LabelDescriptionLookup must be used ' .
				'with an instance of LabelDescriptionLookup.' );
		}

		return $labelDescriptionLookup;
	}

	private function newLanguageFallbackLabelDescriptionLookup( FormatterOptions $options ) {
		$fallbackChain = $options->getOption( 'languages' );

		if ( !( $fallbackChain instanceof LanguageFallbackChain ) ) {
			throw new InvalidArgumentException( 'Option `languages` must be used ' .
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
