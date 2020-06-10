<?php

namespace Wikibase\Repo\Rdf;

use DataValues\IllegalValueException;
use DataValues\TimeValue;

/**
 * Clean datetime value to conform to RDF/XML standards
 * This class supports Julian->Gregorian conversion
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 * @author Thiemo Kreuz
 */
class JulianDateTimeValueCleaner extends DateTimeValueCleaner {

	/**
	 * Get standardized dateTime value, compatible with xsd:dateTime
	 * If the value cannot be converted to it, returns null
	 *
	 * @param TimeValue $value
	 *
	 * @return string|null Value compatible with xsd:dateTime type, null if the input is an illegal
	 *  XSD 1.0 timestamp
	 */
	public function getStandardValue( TimeValue $value ) {
		try {
			// If we are less precise than a day, no point to convert
			// Julian to Gregorian since we don't have enough information to do it anyway
			if ( $value->getCalendarModel() === TimeValue::CALENDAR_JULIAN
				&& $value->getPrecision() >= TimeValue::PRECISION_DAY
			) {
				// There is a certain range of years PHP can convert; assume everything else was a
				// mistake and meant to be a Gregorian date
				return $this->julianDateValue( $value->getTime() )
					?: $this->cleanupGregorianValue( $value->getTime(), $value->getPrecision() );
			}
		} catch ( IllegalValueException $e ) {
			return null;
		}

		return parent::getStandardValue( $value );
	}

	/**
	 * Get Julian date value and return it as Gregorian date
	 *
	 * @param string $dateValue
	 *
	 * @throws IllegalValueException if the input is an illegal XSD 1.0 timestamp
	 * @return string|null Value compatible with xsd:dateTime type, null if conversion is not
	 *  possible
	 */
	private function julianDateValue( $dateValue ) {
		list( $minus, $y, $m, $d, $time ) = $this->parseDateValue( $dateValue );

		$y = $minus ? -$y : $y + 0;
		// There are some weird overflows happening in the PHP functions before and after this year.
		if ( !is_int( $y ) || $y < -4713 || $y > 1465072 ) {
			return null;
		}

		$jd = juliantojd( $m, $d, $y );
		if ( $jd == 0 ) {
			return null;
		}

		$gregorian = jdtogregorian( $jd );
		if ( $gregorian === '0/0/0' ) {
			return null;
		}

		list( $m, $d, $y ) = explode( '/', $gregorian );

		if ( $this->xsd11 && $y < 0 ) {
			// To make year match XSD 1.1 we need to bump up the negative years by 1
			// We know we have precision here since otherwise we wouldn't convert
			$y++;
		}

		// This is a bit weird since xsd:dateTime requires >=4 digit always,
		// and leading 0 is not allowed for 5 digits, but sprintf counts - as digit
		// See: http://www.w3.org/TR/xmlschema-2/#dateTime
		return sprintf( '%s%04d-%02d-%02dT%s', ( $y < 0 ) ? '-' : '', abs( $y ), $m, $d, $time );
	}

}
