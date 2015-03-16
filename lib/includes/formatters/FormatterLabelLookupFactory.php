<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelLookup;
use Wikibase\Lib\Store\LanguageLabelLookup;
use Wikibase\Lib\Store\TermLookup;

/**
 * Factory for LabelLookup objects based on FormatterOptions.
 *
 * The LabelLookup is created based on the values of the options
 * OPT_LABEL_LOOKUP, OPT_LANGUAGE_FALLBACK_CHAIN, and ValueFormatter::OPT_LANG:
 *
 * * OPT_LABEL_LOOKUP can be used to provide a custom LabelLookup instance directly
 * * If OPT_LANGUAGE_FALLBACK_CHAIN is set, a LanguageFallbackLabelLookup will be created by
 *   the LanguageFallbackChain contained in that option.
 * * If ValueFormatter::OPT_LANG is set, a LanguageLabelLookup is created
 * * If none of these options is set, an InvalidArgumentException is thrown.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class FormatterLabelLookupFactory {

	const OPT_LABEL_LOOKUP = 'LabelLookup';
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
	 * @return LabelLookup
	 */
	public function getLabelLookup( FormatterOptions $options ) {
		if ( $options->hasOption( self::OPT_LABEL_LOOKUP ) ) {
			return $this->getLabelLookupFromOptions( $options );
		} elseif ( $options->hasOption( self::OPT_LANGUAGE_FALLBACK_CHAIN ) ) {
			return $this->newLanguageFallbackLabelLookup( $options );
		} elseif ( $options->hasOption( ValueFormatter::OPT_LANG ) ) {
			return $this->newLanguageLabelLookup( $options );
		} else {
			throw new InvalidArgumentException( 'OPT_LANG, OPT_LANGUAGE_FALLBACK_CHAIN, '
				. 'or OPT_LABEL_LOOKUP must be set in FormatterOptions.' );
		}
	}

	private function getLabelLookupFromOptions( FormatterOptions $options ) {
		$labelLookup = $options->getOption( self::OPT_LABEL_LOOKUP );

		if ( !( $labelLookup instanceof LabelLookup ) ) {
			throw new InvalidArgumentException( 'OPT_LABEL_LOOKUP must be used ' .
				'with an instance of LabelLookup.' );
		}

		return $labelLookup;
	}

	private function newLanguageFallbackLabelLookup( FormatterOptions $options ) {
		$fallbackChain = $options->getOption( self::OPT_LANGUAGE_FALLBACK_CHAIN );

		if ( !( $fallbackChain instanceof LanguageFallbackChain ) ) {
			throw new InvalidArgumentException( 'OPT_LANGUAGE_FALLBACK_CHAIN must be used ' .
				'with an instance of LanguageFallbackChain.' );
		}

		return new LanguageFallbackLabelLookup( $this->termLookup, $fallbackChain );
	}

	private function newLanguageLabelLookup( FormatterOptions $options ) {
		$languageCode = $options->getOption( ValueFormatter::OPT_LANG );

		if ( !is_string( $languageCode ) ) {
			throw new InvalidArgumentException( 'ValueFormatter::OPT_LANG must be a '
				. 'language code string.' );
		}

		return new LanguageLabelLookup( $this->termLookup, $languageCode );
	}

}
