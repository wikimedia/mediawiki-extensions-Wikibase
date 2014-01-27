<?php

namespace Wikibase\Lib;

use DataValues\TimeValue;
use Language;
use Message;
use ValueFormatters\FormatterOptions;
use ValueFormatters\TimeIsoFormatter;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
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
		 * [2] => 00000002013
		 * [3] => 0000000
		 * [4] => 2013
		 * [5] => 07
		 * [6] => 16
		 * [7] => 01
		 * [8] => 02
		 * [9] => 03
		 */
		$regexSuccess = preg_match( '/^(\+|\-)((\d{7})?(\d{4}))-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z/',
			$extendedIsoTimestamp, $matches );

		//TODO: format values with -
		if( !$regexSuccess || $matches[1] === '-') {
			return $extendedIsoTimestamp;
		}

		// Positive 4-digit year allows using Language object.
		$fourDigitYearTimestamp = str_replace( '+' . $matches[3], '', $extendedIsoTimestamp );
		$timestamp = wfTimestamp( TS_MW, $fourDigitYearTimestamp );

		$localisedDate = $this->language->sprintfDate(
			$this->getDateFormat( $precision ),
			$timestamp
		);

		//If we cant reliably fix the year return the full timestamp,
		//  this should never happen as sprintfDate should always return a 4 digit year
		if( substr_count( $localisedDate, $matches[4] ) !== 1 ) {
			return $extendedIsoTimestamp;
		}

		$localisedDate = str_replace(
			$matches[4],
			$this->formatYear( $matches[2], $precision ),
			$localisedDate
		);

		return $localisedDate;
	}

	/**
	 * Get the dateformat string for the given precision to be used by sprintfDate
	 * @param integer $precision
	 * @return string dateFormat to be used by sprintfDate
	 */
	private function getDateFormat( $precision ) {
		$dateFormat = $this->language->getDateFormatString(
			'date',
			$this->language->getDefaultDateFormat()
		);

		if( $precision < TimeValue::PRECISION_DAY ) {
			// Remove day placeholder:
			$dateFormat = preg_replace( '/((x\w{1})?(j|t)|d)/', '', $dateFormat );
		}

		if( $precision < TimeValue::PRECISION_MONTH ) {
			// Remove month placeholder:
			$dateFormat = preg_replace( '/((x\w{1})?(F|n)|m)/', '', $dateFormat );
		}
		return trim( $dateFormat );
	}

	/**
	 * @param string $fullYear
	 * @param integer $precision
	 *
	 * @return string the formatted year
	 */
	private function formatYear( $fullYear, $precision ) {
		switch( $precision ) {
			case TimeValue::PRECISION_Ga:
				$fullYear = round( $fullYear, -9 );
				$fullYear = substr( $fullYear, 0, -9 );
				return $this->getMessage( 'wikibase-time-precision-Gannum', $fullYear );
			case TimeValue::PRECISION_100Ma:
				$fullYear = round( $fullYear, -8 );
				$fullYear = substr( $fullYear, 0, -6 );
				return $this->getMessage( 'wikibase-time-precision-Mannum', $fullYear );
			case TimeValue::PRECISION_10Ma:
				$fullYear = round( $fullYear, -7 );
				$fullYear = substr( $fullYear, 0, -6 );
				return $this->getMessage( 'wikibase-time-precision-Mannum', $fullYear );
			case TimeValue::PRECISION_Ma:
				$fullYear = round( $fullYear, -6 );
				$fullYear = substr( $fullYear, 0, -6 );
				return $this->getMessage( 'wikibase-time-precision-Mannum', $fullYear );
			case TimeValue::PRECISION_100ka:
				$fullYear = round( $fullYear, -5 );
				return $this->getMessage( 'wikibase-time-precision-annum', $fullYear );
			case TimeValue::PRECISION_10ka:
				$fullYear = round( $fullYear, -4 );
				return $this->getMessage( 'wikibase-time-precision-annum', $fullYear );
			case TimeValue::PRECISION_ka:
				$fullYear = round( $fullYear, -3 );
				$fullYear = substr( $fullYear, 0, -3 );
				return $this->getMessage( 'wikibase-time-precision-millennium', $fullYear );
			case TimeValue::PRECISION_100a:
				$fullYear = round( $fullYear, -2 );
				$fullYear = substr( $fullYear, 0, -2 );
				return $this->getMessage( 'wikibase-time-precision-century', $fullYear );
			case TimeValue::PRECISION_10a:
				$fullYear = round( $fullYear, -1 );
				return $this->getMessage( 'wikibase-time-precision-10annum', $fullYear );
			default:
				//If not one of the above make sure the year have at least 4 digits
				$fullYear = ltrim( $fullYear, '0' );
				$fullYearLength = strlen( $fullYear );
				if( $fullYearLength < 4 ) {
					$fullYear = str_repeat( '0', 4 - $fullYearLength ) . $fullYear;
				}
				//only add separators if there are more than 4 digits
				if( strlen( $fullYear ) > 4 ) {
					$fullYear = $this->language->formatNum( $fullYear );
				}
				return $fullYear;
		}
	}

	/**
	 * @param string $key
	 * @param string $fullYear
	 * @return String
	 */
	private function getMessage( $key, $fullYear ) {
		$message = new Message( $key );
		$message->inLanguage( $this->language );
		$message->numParams( array( $fullYear ) );
		return $message->text();
	}

}
