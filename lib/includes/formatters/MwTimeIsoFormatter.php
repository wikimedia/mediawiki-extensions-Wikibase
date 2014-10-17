<?php

namespace Wikibase\Lib;

use DataValues\TimeValue;
use InvalidArgumentException;
use Language;
use Message;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Adam Shorland
 * @author Thiemo Mättig
 *
 * @todo move me to DataValues-time
 */
class MwTimeIsoFormatter extends ValueFormatterBase {

	/**
	 * @var Language
	 */
	private $language;

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
		if ( !( $value instanceof TimeValue ) ) {
			throw new InvalidArgumentException( 'Value is not a TimeValue.' );
		}

		return $this->formatTimeValue( $value );
	}

	/**
	 * @param TimeValue $timeValue
	 *
	 * @return string
	 */
	private function formatTimeValue( TimeValue $timeValue ) {
		$isoTimestamp = $timeValue->getTime();

		try {
			return $this->getUnocalizedDate( $isoTimestamp, $timeValue->getPrecision() );
		} catch ( InvalidArgumentException $ex ) {
			return $isoTimestamp;
		}
	}

	/**
	 * @param string $isoTimestamp
	 * @param int $precision
	 *
	 * @throws InvalidArgumentException
	 * @return string Formatted date
	 */
	private function getUnocalizedDate( $isoTimestamp, $precision ) {
		// We do not handle parsing arabic, farsi, etc. digits (bug 63732)
		return $this->language->parseFormattedNumber(
			$this->getLocalizedDate( $isoTimestamp, $precision )
		);
	}

	/**
	 * @param string $isoTimestamp
	 * @param int $precision
	 *
	 * @throws InvalidArgumentException
	 * @return string Formatted date
	 */
	private function getLocalizedDate( $isoTimestamp, $precision ) {
		$dateFormat = $this->getDateFormat( $precision );
		$localizedYear = $this->getLocalizedYear( $isoTimestamp, $precision );

		if ( $dateFormat === 'Y' ) {
			return $localizedYear;
		}

		$mwTimestamp = $this->getMwTimestamp( $isoTimestamp, $precision );
		$mwYear        = $this->language->sprintfDate(         'Y', $mwTimestamp );
		$localizedDate = $this->language->sprintfDate( $dateFormat, $mwTimestamp );

		if ( $mwYear !== $localizedYear ) {
			// If we can not reliably fix the year, return the full time stamp. This should
			// never happen as Language::sprintfDate should always return a 4 digit year.
			if ( substr_count( $localizedDate, $mwYear ) !== 1 ) {
				throw new InvalidArgumentException( 'Can not identify year in formatted date.' );
			}

			$localizedDate = str_replace( $mwYear, $localizedYear, $localizedDate );
		}

		return $localizedDate;
	}

	/**
	 * @param int $precision
	 *
	 * @return string Date format string to be used by Language::sprintfDate
	 */
	private function getDateFormat( $precision ) {
		if ( $precision <= TimeValue::PRECISION_YEAR ) {
			return 'Y';
		} elseif ( $precision === TimeValue::PRECISION_MONTH ) {
			$format = $this->language->getDateFormatString( 'monthonly', 'dmy' );
			return sprintf( '%s Y', $this->getMonthFormat( $format ) );
		} else {
			$format = $this->language->getDateFormatString( 'date', 'dmy' );
			return sprintf( '%s %s Y', $this->getDayFormat( $format ), $this->getMonthFormat( $format ) );
		}
	}

	/**
	 * @see Language::sprintfDate
	 *
	 * @param string $dateFormat
	 *
	 * @return string A date format for the day that roundtrips the Wikibase TimeParsers.
	 */
	private function getDayFormat( $dateFormat ) {
		if ( preg_match( '/(?:d|(?<!x)j)[.,]?/', $dateFormat, $matches ) ) {
			return $matches[0];
		}

		return 'j';
	}

	/**
	 * @see Language::sprintfDate
	 *
	 * @param string $dateFormat
	 *
	 * @return string A date format for the month that roundtrips the Wikibase TimeParsers.
	 */
	private function getMonthFormat( $dateFormat ) {
		if ( preg_match( '/(?:[FMn]|(?<!x)m|xg)[.,]?/', $dateFormat, $matches ) ) {
			return $matches[0];
		}

		return 'F';
	}

	/**
	 * @param string $isoTimestamp
	 * @param int $precision
	 *
	 * @throws InvalidArgumentException
	 * @return string MediaWiki time stamp in the format YYYYMMDDHHMMSS
	 */
	private function getMwTimestamp( $isoTimestamp, $precision ) {
		$args = $this->splitIsoTimestamp( $isoTimestamp, $precision );

		// Year must be in the range 0000 to 9999 in an MediaWiki time stamp
		$args[0] = substr( $args[0], -4 );
		// Month/day must default to 1 to not get the last day of the previous year/month
		$args[1] = max( 1, $args[1] );
		$args[2] = max( 1, $args[2] );

		return vsprintf( '%04d%02d%02d%02d%02d%02d', $args );
	}

	/**
	 * @param string $isoTimestamp
	 * @param int $precision
	 *
	 * @throws InvalidArgumentException
	 * @return string[] Year, month, day, hour, minute, second
	 */
	private function splitIsoTimestamp( $isoTimestamp, $precision ) {
		if ( !preg_match(
			'/(\d+)\D+(\d+)\D+(\d+)\D+(\d+)\D+(\d+)\D+(\d+)/',
			$isoTimestamp,
			$matches
		) ) {
			throw new InvalidArgumentException( 'Unable to parse time value.' );
		}

		// The year can easily exceed the 32 bit integer limit
		$year  = floatval( $matches[1] );
		$month =   intval( $matches[2] );
		$day   =   intval( $matches[3] );

		if (   empty( $year  ) && $precision <  TimeValue::PRECISION_YEAR
			|| empty( $month ) && $precision >= TimeValue::PRECISION_MONTH
			|| empty( $day   ) && $precision >= TimeValue::PRECISION_DAY
		) {
			throw new InvalidArgumentException( 'Time value insufficient for precision.' );
		}

		return array_slice( $matches, 1 );
	}

	/**
	 * @param string $isoTimestamp
	 * @param int $precision
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	private function getLocalizedYear( $isoTimestamp, $precision ) {
		$shift = 1e+0;
		$unshift = 1e-0;
		$func = 'round';

		switch ( $precision ) {
			case TimeValue::PRECISION_Ga:
				$msg = 'Gannum';
				$shift = 1e+9;
				break;
			case TimeValue::PRECISION_100Ma:
				$msg = 'Mannum';
				$shift = 1e+8;
				$unshift = 1e+2;
				break;
			case TimeValue::PRECISION_10Ma:
				$msg = 'Mannum';
				$shift = 1e+7;
				$unshift = 1e+1;
				break;
			case TimeValue::PRECISION_Ma:
				$msg = 'Mannum';
				$shift = 1e+6;
				break;
			case TimeValue::PRECISION_100ka:
				$msg = 'annum';
				$shift = 1e+5;
				$unshift = 1e+5;
				break;
			case TimeValue::PRECISION_10ka:
				$msg = 'annum';
				$shift = 1e+4;
				$unshift = 1e+4;
				break;
			case TimeValue::PRECISION_ka:
				$msg = 'millennium';
				$func = 'ceil';
				$shift = 1e+3;
				break;
			case TimeValue::PRECISION_100a:
				$msg = 'century';
				$func = 'ceil';
				$shift = 1e+2;
				break;
			case TimeValue::PRECISION_10a:
				$msg = '10annum';
				$func = 'floor';
				$shift = 1e+1;
				$unshift = 1e+1;
				break;
		}

		$isBCE = substr( $isoTimestamp, 0, 1 ) === '-';
		$year = abs( floatval( $isoTimestamp ) );

		switch ( $func ) {
			case 'ceil':
				$number = round( ceil( $year / $shift ) * $unshift );
				break;
			case 'floor':
				$number = round( floor( $year / $shift ) * $unshift );
				break;
			default:
				$number = round( round( $year / $shift ) * $unshift );
		}

		// Year to small for precision, fall back to year
		if ( empty( $number )
			&& ( $precision < TimeValue::PRECISION_YEAR
				|| ( $isBCE && $precision === TimeValue::PRECISION_YEAR )
			)
		) {
			$msg = null;
			$number = $year;
			$isBCE = $isBCE && !empty( $number );
		}

		$formattedNumber = $this->language->formatNum( $number, true );

		if ( empty( $msg ) ) {
			// TODO: This needs a message.
			return $formattedNumber . ( $isBCE ? ' BCE' : '' );
		}

		return $this->getMessage(
			'wikibase-time-precision-' . ( $isBCE ? 'BCE-' : '' ) . $msg,
			$formattedNumber
		);
	}

	/**
	 * @param string $key
	 * @param string $param
	 *
	 * @return string
	 */
	private function getMessage( $key, $param ) {
		$message = new Message( $key );
		// FIXME: As the frontend can not parse the translated precisions we only want to
		// present the English for now. Once the frontend is using backend parsers we can
		// turn the translation on. See the FIXME in MwTimeIsoParser::reconvertOutputString.
		// $message->inLanguage( $this->language );
		$message->inLanguage( new Language() );
		$message->params( array( $param ) );
		return $message->text();
	}

}
