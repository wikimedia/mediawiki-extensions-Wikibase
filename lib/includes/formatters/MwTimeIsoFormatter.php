<?php

namespace Wikibase\Lib;

use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\TimeIsoFormatter;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
class MwTimeIsoFormatter extends ValueFormatterBase implements TimeIsoFormatter {

	/**
	 * MediaWiki language object.
	 * @var Language
	 */
	protected $language;

	/**
	 * @param FormatterOptions $options
	 */
	public function __construct( FormatterOptions $options ) {
		$this->options = $options;

		$this->options->defaultOption( ValueFormatter::OPT_LANG, 'en' );

		$this->language = Language::factory(
			$this->options->getOption( ValueFormatter::OPT_LANG )
		);
	}

	/**
 	 * @see ValueFormatter::format
	 */
	public function format( $value ) {
		return $this->formatDate(
			$value->getTime(),
			$value->getPrecision()
		);
	}

	/**
	 * @see TimeIsoFormatter::formatDate
	 */
	public function formatDate( $extendedIsoTimestamp, $precision ) {
		if(
			// TODO: Localize dates not featuring a positive 4-digit year.
			preg_match( '/^\+0*(\d{4})-/', $extendedIsoTimestamp, $matches )
			// TODO: Support precision above year
			&& $precision >= 9
		) {
			// Positive 4-digit year allows using Language object.
			$strippedTime = preg_replace( '/^(\+0*)(\d{4})/', '$2', $extendedIsoTimestamp );

			$timestamp = wfTimestamp( TS_MW, $strippedTime );
			$dateFormat = $this->language->getDateFormatString(
				'date',
				$this->language->getDefaultDateFormat()
			);

			// TODO: Implement more sophisticated replace algorithm since characters may be escaped
			//  or, even better, find a way to avoid having to do replacements.
			if( $precision < 11 ) {
				// Remove day placeholder:
				$dateFormat = preg_replace( '/((x\w{1})?(j|t)|d)/', '', $dateFormat );
			}

			if( $precision < 10 ) {
				// Remove month placeholder:
				$dateFormat = preg_replace( '/((x\w{1})?(F|n)|m)/', '', $dateFormat );
			}

			// TODO: Currently, the year will always be formatted with 4 digits. Years < 1000 will
			//  features leading zero(s) that would need to be stripped.
			return $this->language->sprintfDate( trim( $dateFormat ), $timestamp );
		} else {
			return $extendedIsoTimestamp;
		}
	}

}
