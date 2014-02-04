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
 *
 * @todo move me to DataValues-time
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
		$regexSuccess = preg_match( '/^(\+|\-)((\d{0,7})?(\d{4}))-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z/',
			$extendedIsoTimestamp, $matches );

		if( !$regexSuccess || intval( $matches[2] ) === 0 ) {
			return $extendedIsoTimestamp;
		}
		$isBCE = ( $matches[1] === '-' );

		// Positive 4-digit year allows using Language object.
		$fourDigitYearTimestamp = str_pad(
			substr( $extendedIsoTimestamp, strlen( $matches[1] . $matches[3] ) ),
			20, // This is the length of 2013-07-16T00:00:00Z
			'0',
			STR_PAD_LEFT
		);

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
			$this->formatYear( $matches[2], $precision, $isBCE ),
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
	 * @param bool $isBCE
	 *
	 * @return string the formatted year
	 */
	private function formatYear( $fullYear, $precision, $isBCE ) {
		if( $isBCE ) {
			$msgPrefix = 'wikibase-time-precision-BCE';
		} else {
			$msgPrefix = 'wikibase-time-precision';
		}

		switch( $precision ) {
			case TimeValue::PRECISION_Ga:
				$fullYear = round( $fullYear, -9 );
				$fullYear = substr( $fullYear, 0, -9 );
				return $this->getMessage( $msgPrefix . '-Gannum', $fullYear );
			case TimeValue::PRECISION_100Ma:
				$fullYear = round( $fullYear, -8 );
				$fullYear = substr( $fullYear, 0, -6 );
				return $this->getMessage( $msgPrefix . '-Mannum', $fullYear );
			case TimeValue::PRECISION_10Ma:
				$fullYear = round( $fullYear, -7 );
				$fullYear = substr( $fullYear, 0, -6 );
				return $this->getMessage( $msgPrefix . '-Mannum', $fullYear );
			case TimeValue::PRECISION_Ma:
				$fullYear = round( $fullYear, -6 );
				$fullYear = substr( $fullYear, 0, -6 );
				return $this->getMessage( $msgPrefix . '-Mannum', $fullYear );
			case TimeValue::PRECISION_100ka:
				$fullYear = round( $fullYear, -5 );
				return $this->getMessage( $msgPrefix . '-annum', $fullYear );
			case TimeValue::PRECISION_10ka:
				$fullYear = round( $fullYear, -4 );
				return $this->getMessage( $msgPrefix . '-annum', $fullYear );
			case TimeValue::PRECISION_ka:
				$fullYear = round( $fullYear, -3 );
				$fullYear = substr( $fullYear, 0, -3 );
				return $this->getMessage( $msgPrefix . '-millennium', $fullYear );
			case TimeValue::PRECISION_100a:
				$fullYear = round( $fullYear, -2 );
				$fullYear = substr( $fullYear, 0, -2 );
				return $this->getMessage( $msgPrefix . '-century', $fullYear );
			case TimeValue::PRECISION_10a:
				$fullYear = round( $fullYear, -1 );
				return $this->getMessage( $msgPrefix . '-10annum', $fullYear );
			default:
				//If not one of the above make sure the year have at least 4 digits
				$fullYear = ltrim( $fullYear, '0' );
				if( $isBCE ) {
					$fullYear .= ' BCE';
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
		//FIXME: as the frontend can not parse the translated precisions we only want to present the ENGLISH for now
		//once the frontend is using backend parsers we can switch the translation on
		//See the fix me in: MwTimeIsoParser::reconvertOutputString
		//$message->inLanguage( $this->language );
		$message->inLanguage( new Language() );
		$message->params( array( $fullYear ) );
		return $message->text();
	}

}
