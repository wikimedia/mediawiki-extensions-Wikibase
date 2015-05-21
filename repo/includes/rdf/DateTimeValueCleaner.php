<?php
namespace Wikibase;

use DataValues\TimeValue;

/**
 * Very basic cleaner that assumes the date is Gregorian and only
 * ensures it looks OK.
 *
 * @licence GNU GPL v2+
 */
class DateTimeValueCleaner {
	/**
	 * Clean up Wikidata date value in Gregorian calendar
	 * - remove + from the start - not all data stores like that
	 * - validate month and date value
	 * @param string $dateValue
	 * @return string Value compatible with xsd:dateTime type
	 */
	protected function cleanupGregorianValue( $dateValue ) {
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
			// PHP source docs say PHP gregorian calendar can work down to 4714 BC
			// Use float conversion here since we don't care about precision but don't want overflows.
			if( $minus && (float)$y >= 4714 ) {
				$safeYear = -4713;
			} else {
				$safeYear = (int)$y * ($minus?-1:1);
			}

			// This will convert $y to int. If it's not within sane range,
			// Feb 29 may be mangled, but this will be rare.
			$max = cal_days_in_month( CAL_GREGORIAN, $m, $safeYear );
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
	 * Get standardized dateTime value, compatible with xsd:dateTime
	 * If the value cannot be converted to it, returns null
	 * @param TimeValue $value
	 * @return string|null
	 */
	public function getStandardValue( TimeValue $value ) {
		return $this->cleanupGregorianValue( $value->getTime() );
	}
}
