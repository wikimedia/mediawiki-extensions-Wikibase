<?php

namespace Wikibase\Lib\Formatters;

use DataValues\TimeValue;
use InvalidArgumentException;
use Language;
use MediaWiki\Languages\LanguageFactory;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 * @author Addshore
 * @author Thiemo Kreuz
 *
 * @todo move me to DataValues-time
 */
class MwTimeIsoFormatter implements ValueFormatter {

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var FormatterOptions
	 */
	private $options;

	/**
	 * @param LanguageFactory $languageFactory
	 * @param FormatterOptions|null $options
	 */
	public function __construct(
		LanguageFactory $languageFactory,
		FormatterOptions $options = null
	) {
		$this->options = $options ?: new FormatterOptions();
		$this->options->defaultOption( ValueFormatter::OPT_LANG, 'en' );

		$languageCode = $this->options->getOption( ValueFormatter::OPT_LANG );
		$this->language = $languageFactory->getLanguage( $languageCode );
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * @param TimeValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string Text
	 */
	public function format( $value ) {
		if ( !( $value instanceof TimeValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a TimeValue.' );
		}

		return $this->formatTimeValue( $value );
	}

	/**
	 * @param TimeValue $timeValue
	 *
	 * @return string Text
	 */
	private function formatTimeValue( TimeValue $timeValue ) {
		$isoTimestamp = $timeValue->getTime();

		try {
			return $this->getLocalizedDate( $isoTimestamp, $timeValue->getPrecision() );
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
	private function getLocalizedDate( $isoTimestamp, $precision ) {
		$localizedYear = $this->getLocalizedYear( $isoTimestamp, $precision );

		if ( $precision <= TimeValue::PRECISION_YEAR ) {
			return $localizedYear;
		}

		$dateFormat = $this->getDateFormat( $precision );
		$mwTimestamp = $this->getMwTimestamp( $isoTimestamp, $precision );
		$mwYear = $this->language->sprintfDate( 'Y', $mwTimestamp );
		$localizedDate = $this->language->sprintfDate( $dateFormat, $mwTimestamp );

		if ( $mwYear !== $localizedYear ) {
			// Check if we can reliably fix the year. This should never fail as
			// Language::sprintfDate should always return a 4 digit year.
			if ( substr_count( $localizedDate, $mwYear ) !== 1 ) {
				throw new InvalidArgumentException( 'Cannot identify year in formatted date.' );
			}

			$localizedDate = str_replace( $mwYear, $localizedYear, $localizedDate );
		}

		return $localizedDate;
	}

	/**
	 * @param int $precision
	 *
	 * @throws InvalidArgumentException
	 * @return string Date format string to be used by Language::sprintfDate
	 */
	private function getDateFormat( $precision ) {
		$datePreference = 'default';

		$datePreferences = $this->language->getDatePreferences();
		if ( is_array( $datePreferences ) && in_array( 'dmy', $datePreferences ) ) {
			$datePreference = 'dmy';
		}

		if ( $precision === TimeValue::PRECISION_MONTH ) {
			$format = $this->language->getDateFormatString( 'monthonly', $datePreference );
			return sprintf( '%s Y', $this->getMonthFormat( $format ) );
		} elseif ( $precision === TimeValue::PRECISION_DAY ) {
			$format = $this->language->getDateFormatString( 'date', $datePreference );
			return sprintf( '%s %s Y', $this->getDayFormat( $format ), $this->getMonthFormat( $format ) );
		} else {
			throw new InvalidArgumentException( 'Unsupported precision' );
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

		list( , $year, $month, $day ) = $matches;

		if ( $year == 0 && $precision < TimeValue::PRECISION_YEAR
			|| $month == 0 && $precision >= TimeValue::PRECISION_MONTH
			|| $day == 0 && $precision >= TimeValue::PRECISION_DAY
		) {
			throw new InvalidArgumentException( 'Time value insufficient for precision.' );
		}

		return array_slice( $matches, 1 );
	}

	/**
	 * @param string $isoTimestamp
	 * @param int $precision
	 *
	 * @return string
	 */
	private function getLocalizedYear( $isoTimestamp, $precision ) {
		preg_match( '/^(\D*)(\d*)/', $isoTimestamp, $matches );
		list( , $sign, $year ) = $matches;
		$isBCE = $sign === '-';

		$shift = 1;
		$unshift = 1;
		$func = 'round';

		switch ( $precision ) {
			case TimeValue::PRECISION_YEAR1G:
				$msg = 'Gannum';
				$shift = 1e+9;
				break;
			case TimeValue::PRECISION_YEAR100M:
				$msg = 'Mannum';
				$shift = 1e+8;
				$unshift = 1e+2;
				break;
			case TimeValue::PRECISION_YEAR10M:
				$msg = 'Mannum';
				$shift = 1e+7;
				$unshift = 1e+1;
				break;
			case TimeValue::PRECISION_YEAR1M:
				$msg = 'Mannum';
				$shift = 1e+6;
				break;
			case TimeValue::PRECISION_YEAR100K:
				$msg = 'annum';
				$shift = 1e+5;
				$unshift = 1e+5;
				break;
			case TimeValue::PRECISION_YEAR10K:
				$msg = 'annum';
				$shift = 1e+4;
				$unshift = 1e+4;
				break;
			case TimeValue::PRECISION_YEAR1K:
				$msg = 'millennium';
				$func = 'ceil';
				$shift = 1e+3;
				break;
			case TimeValue::PRECISION_YEAR100:
				$msg = 'century';
				$func = 'ceil';
				$shift = 1e+2;
				break;
			case TimeValue::PRECISION_YEAR10:
				$msg = '10annum';
				$func = 'floor';
				$shift = 1e+1;
				$unshift = 1e+1;
				break;
		}

		$shifted = $this->shiftNumber( $year, $func, $shift, $unshift );
		if ( $shifted == 0
			&& ( $precision < TimeValue::PRECISION_YEAR
				|| ( $isBCE && $precision === TimeValue::PRECISION_YEAR )
			)
		) {
			// Year to small for precision, fall back to year.
			$msg = null;
		} else {
			$year = $shifted;
		}

		$year = str_pad( ltrim( $year, '0' ), 1, '0', STR_PAD_LEFT );
		// TODO: The year should be localized via Language::formatNum() at this point, but currently
		// can't because not all relevant time parsers unlocalize numbers.

		if ( empty( $msg ) ) {
			if ( $isBCE ) {
				return wfMessage(
					'wikibase-time-precision-BCE',
					$year
				)
				->inLanguage( $this->language )
				->text();
			} elseif ( strlen( $year ) <= 2 ) {
				return wfMessage(
					'wikibase-time-precision-CE',
					$year
				)
				->inLanguage( $this->language )
				->text();
			} else {
				return $year;
			}
		}

		return wfMessage(
			'wikibase-time-precision-' . ( $isBCE ? 'BCE-' : '' ) . $msg,
			$year
		)
		->inLanguage( $this->language )
		->text();
	}

	/**
	 * @param string $number
	 * @param string $function
	 * @param float $shift
	 * @param float $unshift
	 *
	 * @return string
	 */
	private function shiftNumber( $number, $function, $shift, $unshift ) {
		if ( $shift == 1 && $unshift == 1 ) {
			return $number;
		}

		switch ( $function ) {
			case 'ceil':
				$shifted = ceil( $number / $shift ) * $unshift;
				break;
			case 'floor':
				$shifted = floor( $number / $shift ) * $unshift;
				break;
			default:
				$shifted = round( $number / $shift ) * $unshift;
		}

		return sprintf( '%.0f', $shifted );
	}

}
