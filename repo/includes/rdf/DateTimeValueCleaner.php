<?php
namespace Wikibase;

use DataValues\TimeValue;

/**
 * Clean datetime value to conform to RDF/XML standards
 *
 * @licence GNU GPL v2+
 */
class DateTimeValueCleaner {
	// Gregorian calendar link.
	// I'm not very happy about hardcoding it here but see no better way so far
	const GREGORIAN_CALENDAR = 'http://www.wikidata.org/entity/Q1985727';
	const JULIAN_CALENDAR = 'http://www.wikidata.org/entity/Q1985786';

	/**
	 * Clean up Wikidata date value in Gregorian calendar
	 * - remove + from the start - not all data stores like that
	 * - validate month and date value
	 * @param string $dateValue
	 * @return string Value compatible with xsd:dateTime type
	 */
	protected function cleanupDateValue( $dateValue ) {
		list($date, $time) = explode( "T", $dateValue, 2 );
		if( $date[0] == "-" ) {
			$minus = "-";
		} else {
			$minus = "";
		}
		list($y, $m, $d) = explode( "-", substr( $date, 1 ), 3 );

		$m = (int)$m;
		$d = (int)$d;
		$y = ltrim($y, '0');

		if($y === "") {
			// Year 0 is invalid for now, see T94064 for discussion
			return null;
		}

		// PHP source docs say PHP gregorian calendar can work down to 4714 BC
		// for smaller dates, we ignore month/day.
		// Use float conversion here since we don't care about precision but don't want overflows.
		if( $minus && (float)$y >= 4714 ) {
			$d = $m = 1;
		}

		if( $m <= 0 ) {
			$m = 1;
		}
		if( $m >= 12 ) {
			// Why anybody would do something like that? Anyway, better to check.
			$m = 12;
		}
		if( $d <= 0 ) {
			$d = 1;
		}
		// check if the date "looks safe". If not, we do deeper check
		if( !( $d <= 28 || ( $m != 2 && $d <= 30 ) ) ) {
			// This will convert $y to int. If it's not within sane range,
			// day/month doesn't make much sense anyway.
			$max = cal_days_in_month( CAL_GREGORIAN, $m, $minus ? -1*$y : $y );
			// We just put it as the last day in month, won't bother further
			if( $d > $max ) {
				$d = $max;
			}
		}
		// This is a bit weird since xsd:dateTime requires >=4 digit always,
		// and leading 0 is not allowed for 5 digits
		// But sprintf counts - as digit
		// See: http://www.w3.org/TR/xmlschema-2/#dateTime
		return sprintf( "%s%04s-%02d-%02dT%s", $minus, $y, $m, $d, $time );
	}

	/**
	 * Get Julian date value and return it as Gregorian date
	 * @param string $dateValue
	 * @return string|null Value compatible with xsd:dateTime type, null if we failed to parse
	 */
	protected function julianDateValue( $dateValue ) {
		list($date, $time) = explode( "T", $dateValue, 2 );
		if ( $date[0] == '-' ) {
			list($y, $m, $d) = explode( "-", substr( $date, 1 ), 3 );
			$y = -(int)$y;
		} else {
			list($y, $m, $d) = explode( "-", $date, 3 );
			$y = (int)$y;
		}
		// cal_to_jd needs int year
		// If it's too small it's fine, we'll get 0
		// If it's too big, it doesn't make sense anyway since who uses Julian in year 2 billion?
		$jd = cal_to_jd( CAL_JULIAN, $m, $d, (int)$y );
		if( $jd == 0 ) {
			// that means the date is broken
			return null;
		}
		// PHP API for Julian is kind of awful
		list($m, $d, $y) = explode( '/', jdtogregorian( $jd ) );
		// This is a bit weird since xsd:dateTime requires >=4 digit always,
		// and leading 0 is not allowed for 5 digits
		// But sprintf counts - as digit
		// See: http://www.w3.org/TR/xmlschema-2/#dateTime
		return sprintf( "%s%04d-%02d-%02dT%s", ($y < 0)? "-":"", abs( $y ), $m, $d, $time );
	}

	/**
	 * Get standardized dateTime value, compatible with xsd:dateTime
	 * If the value can not be converted to it, returns null
	 * @param TimeValue $value
	 * @return string|null
	 */
	public function getStandardValue( TimeValue $value ) {
		$calendar = $value->getCalendarModel();
		if( $calendar == self::GREGORIAN_CALENDAR ) {
			return self::cleanupDateValue( $value->getTime() );
		} else if( $calendar == self::JULIAN_CALENDAR ) {
			return self::julianDateValue( $value->getTime() );
		}
		return null;
	}
}