<?php

namespace Wikibase\Lib;

use DataValues\TimeValue;
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
 * @author Adam Shorland
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
		/**
		 * $matches for +00000002013-07-16T01:02:03Z
		 * [0] => +00000002013-07-16T00:00:00Z
		 * [1] => +
		 * [2] => 0000000
		 * [3] => 2013
		 * [4] => 07
		 * [5] => 16
		 * [6] => 01
		 * [7] => 02
		 * [8] => 03
		 */
		$regexSuccess = preg_match( '/^(\+|\-)(\d{7})?(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z/',
			$extendedIsoTimestamp, $matches );

		//TODO: format values with - or more precise than a year
		if( !$regexSuccess || $matches[1] === '-' || $precision < TimeValue::PRECISION_YEAR ) {
			return $extendedIsoTimestamp;
		}

		// Positive 4-digit year allows using Language object.
		$fourDigitYearTimestamp = str_replace( '+' . $matches[2], '', $extendedIsoTimestamp );
		$timestamp = wfTimestamp( TS_MW, $fourDigitYearTimestamp );
		$dateFormat = $this->language->getDateFormatString(
			'date',
			$this->language->getDefaultDateFormat()
		);

		// TODO: Implement more sophisticated replace algorithm since characters may be escaped
		//  or, even better, find a way to avoid having to do replacements.
		if( $precision < TimeValue::PRECISION_DAY ) {
			// Remove day placeholder:
			$dateFormat = preg_replace( '/((x\w{1})?(j|t)|d)/', '', $dateFormat );
		}

		if( $precision < TimeValue::PRECISION_MONTH ) {
			// Remove month placeholder:
			$dateFormat = preg_replace( '/((x\w{1})?(F|n)|m)/', '', $dateFormat );
		}

		$localisedDate = $this->language->sprintfDate( trim( $dateFormat ), $timestamp );

		//If we cant reliably fix the year return the full timestamp,
		//  this should never happen as sprintfDate should always return a 4 digit year
		if( substr_count( $localisedDate, $matches[3] ) !== 1 ) {
			return $extendedIsoTimestamp;
		}

		//todo optional trimming through options?
		$fullYear = $matches[2] . $matches[3];
		$fullYear = ltrim( $fullYear, '0' );
		$fullYearLength = strlen( $fullYear );
		if( $fullYearLength < 4 ) {
			$fullYear = str_repeat( '0', 4 - $fullYearLength ) . $fullYear;
		}

		$localisedDate = str_replace( $matches[3], $fullYear, $localisedDate );

		return $localisedDate;
	}

}
