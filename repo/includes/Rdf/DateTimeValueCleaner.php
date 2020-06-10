<?php

namespace Wikibase\Repo\Rdf;

use DataValues\IllegalValueException;
use DataValues\TimeValue;

/**
 * Very basic cleaner that assumes the date is Gregorian and only
 * ensures it looks OK.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 * @author Thiemo Kreuz
 */
class DateTimeValueCleaner {

	/**
	 * Are we using XSD 1.1 standard or XSD 1.0?
	 * XSD 1.1 has year 0 and it's 1 BCE
	 * XSD 1.0 doesn't have year 0 and year -1 is 1 BCE
	 * Internally, 1BCE is represented as -0001, same does PHP
	 * @var bool
	 */
	protected $xsd11 = true;

	/**
	 * @param bool $xsd11 Should we use XSD 1.1 standard?
	 */
	public function __construct( $xsd11 = true ) {
		$this->xsd11 = $xsd11;
	}

	/**
	 * Get standardized dateTime value, compatible with xsd:dateTime
	 * If the value cannot be converted to it, returns null
	 * @param TimeValue $value
	 * @return string|null
	 */
	public function getStandardValue( TimeValue $value ) {
		// If we are less precise than a day, the calendar model does not matter
		// since we don't have enough information to do conversion anyway
		if ( $value->getCalendarModel() === TimeValue::CALENDAR_GREGORIAN
			|| $value->getPrecision() < TimeValue::PRECISION_DAY
		) {
			return $this->cleanupGregorianValue( $value->getTime(), $value->getPrecision() );
		}

		return null;
	}

	/**
	 * Clean up Wikidata date value in Gregorian calendar
	 * - remove + from the start - not all data stores like that
	 * - validate month and date value
	 *
	 * @param string $dateValue
	 * @param int $precision Date precision constant (e.g. TimeValue::PRECISION_SECOND)
	 *
	 * @return string|null Value compatible with xsd:dateTime type, null if we failed to parse
	 */
	protected function cleanupGregorianValue( $dateValue, $precision ) {
		try {
			list( $minus, $y, $m, $d, $time ) = $this->parseDateValue( $dateValue );
		} catch ( IllegalValueException $e ) {
			return null;
		}

		if ( $precision <= TimeValue::PRECISION_YEAR ) {
			// If we don't have day precision, don't bother cleaning up day values
			$d = 1;
			$m = 1;
		} elseif ( $precision == TimeValue::PRECISION_MONTH ) {
			$d = 1;
		}

		// check if the date "looks safe". If not, we do deeper check
		if ( !( $d <= 28 || ( $m != 2 && $d <= 30 ) ) ) {
			// PHP source docs say PHP gregorian calendar can work down to 4714 BC
			// Use float conversion here since we don't care about precision but don't want overflows.
			if ( $minus && (float)$y >= 4714 ) {
				$safeYear = -4713;
			} else {
				$safeYear = (int)$y * ( $minus ? -1 : 1 );
			}

			// This will convert $y to int. If it's not within sane range,
			// Feb 29 may be mangled, but this will be rare.
			$max = cal_days_in_month( CAL_GREGORIAN, $m, $safeYear );
			// We just put it as the last day in month, won't bother further
			if ( $d > $max ) {
				$d = $max;
			}
		}

		if ( $this->xsd11 && $precision >= TimeValue::PRECISION_YEAR && $minus ) {
			// If we have year's or finer precision, to make year match XSD 1.1 we
			// need to bump up the negative years by 1
			// Note that $y is an absolute value here.
			$y = number_format( (float)$y - 1, 0, '', '' );
			if ( $y == "0" ) {
				$minus = "";
			}
			// Note that if year is very large, we could lose precision here, but
			// we should not have very large years with year precision
		}

		// This is a bit weird since xsd:dateTime requires >=4 digit always,
		// and leading 0 is not allowed for 5 digits, but sprintf counts - as digit
		// See: http://www.w3.org/TR/xmlschema-2/#dateTime
		return sprintf( '%s%04s-%02d-%02dT%s', $minus, $y, $m, $d, $time );
	}

	/**
	 * Parse date value and fix weird numbers there.
	 *
	 * @param string $dateValue
	 *
	 * @throws IllegalValueException if the input is an illegal XSD 1.0 timestamp
	 * @return array Parsed value in parts: $minus, $y, $m, $d, $time
	 */
	protected function parseDateValue( $dateValue ) {
		list( $date, $time ) = explode( "T", $dateValue, 2 );
		if ( $date[0] == "-" ) {
			$minus = "-";
		} else {
			$minus = '';
		}
		list( $y, $m, $d ) = explode( '-', substr( $date, 1 ), 3 );

		$m = (int)$m;
		$d = (int)$d;
		$y = ltrim( $y, '0' );

		if ( $m <= 0 ) {
			$m = 1;
		}
		if ( $m >= 12 ) {
			// Why anybody would do something like that? Anyway, better to check.
			$m = 12;
		}
		if ( $d <= 0 ) {
			$d = 1;
		}

		if ( $y === "" ) {
			// Year 0 is invalid for now, see T94064 for discussion
			throw new IllegalValueException();
		}

		return [ $minus, $y, $m, $d, $time ];
	}

}
