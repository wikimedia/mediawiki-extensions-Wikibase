<?php
namespace Wikibase;

use DataValues\TimeValue;

/**
 * Clean datetime value to conform to RDF/XML standards
 * This class supports Julian->Gregorian conversion
 * @licence GNU GPL v2+
 */
class JulianDateTimeValueCleaner extends DateTimeValueCleaner {
	// I'm not very happy about hardcoding it here but see no better way so far
	// Gregorian calendar link.
	const GREGORIAN_CALENDAR = 'http://www.wikidata.org/entity/Q1985727';
	// Julian calendar link.
	const JULIAN_CALENDAR = 'http://www.wikidata.org/entity/Q1985786';

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
			return $this->cleanupGregorianValue( $value->getTime() );
		} elseif ( $calendar == self::JULIAN_CALENDAR ) {
			return $this->julianDateValue( $value->getTime() );
		}
		return null;
	}
}