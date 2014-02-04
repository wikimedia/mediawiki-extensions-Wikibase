<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use ValueParsers\CalenderModelParser;
use ValueParsers\ParseException;
use ValueParsers\StringValueParser;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class YearMonthTimeParser extends StringValueParser {

	const FORMAT_STRING = 'Y-m-d\TH:i:s\Z';

	/**
	 * Parses the provided string and returns the result.
	 *
	 * @param string $value
	 *
	 * @throws ParseException
	 * @return TimeValue
	 */
	protected function stringParse( $value ) {
		if( !preg_match( '/^([\d\w\.\,]+)[\/\-\s]([\d\w\.\,]+)$/', trim( $value ), $matches ) ) {
			throw new ParseException( 'Failed to parse year and month' );
		}
		list( ,$a, $b ) = $matches;

		$aIsInt = $this->getIsInt( $a );
		$bIsInt = $this->getIsInt( $b );

		if( $aIsInt && $bIsInt ) {
			$parsed = $this->parseTwoInts( $a, $b );
			if( $parsed ) {
				return $parsed;
			}
		}

		if( $aIsInt || $bIsInt ) {
			if( $aIsInt ) {
				$year = $a;
				$month = trim( $b );
			} else {
				$year = $b;
				$month = trim( $a );
			}

			$parsed =  $this->paserYearMonth( $year, $month );
			if( $parsed ) {
				return $parsed;
			}
		}

		throw new ParseException( 'Failed to parse year and month' );
	}

	/**
	 * @param string $value
	 *
	 * @return bool
	 */
	private function getIsInt( $value ) {
		if( preg_match( '/[\d\.\,]+/', $value ) ) {
			return true;
		}
		return false;
	}

	/**
	 * If we have 2 integers parse the date assuming that the larger is the year
	 * unless the smaller is not a 'legal' month value
	 *
	 * @param string|int $a
	 * @param string|int $b
	 *
	 * @return TimeValue|bool
	 */
	private function parseTwoInts( $a, $b  ) {
		global $wgLang;

		$a = $wgLang->parseFormattedNumber( $a );
		$b = $wgLang->parseFormattedNumber( $b );

		if( $a > $b && $this->canBeMonth( $b ) ) {
			return $this->getTimeFromYearMonth( $a, $b );
		} else if( $this->canBeMonth( $a ) ) {
			return $this->getTimeFromYearMonth( $b, $a );
		}

		return false;
	}

	/**
	 * If we have 1 int and 1 string then try to parse the int as the year and month as the string
	 * Check for both the full name and abbreviations
	 *
	 * @param string|int $year
	 * @param string|int $month
	 *
	 * @return TimeValue|bool
	 */
	private function paserYearMonth( $year, $month ) {
		global $wgLang;

		$year = $wgLang->parseFormattedNumber( $year );

		if( $this->canBeMonth( $month ) ) {
			$names = $wgLang->getMonthNamesArray();
			for ( $i = 1; $i < 13; $i++ ) {
				if( strcasecmp( $names[$i], $month ) === 0 ) {
					return $this->getTimeFromYearMonth( $year, $i );
				}
			}
			$nameAbbrevs = $wgLang->getMonthAbbreviationsArray();
			for ( $i = 1; $i < 13; $i++ ) {
				if( strcasecmp( $nameAbbrevs[$i], $month ) === 0 ) {
					return $this->getTimeFromYearMonth( $year, $i );
				}
			}
		}

		return false;
	}

	/**
	 * @param string $year
	 * @param string $month
	 * @return TimeValue
	 */
	private function getTimeFromYearMonth( $year, $month ) {
		if( strlen( $month ) === 1 ) {
			$month = '0' . $month;
		}
		$timeParser = new \ValueParsers\TimeParser( new CalenderModelParser(), $this->getOptions() );
		return $timeParser->parse( '+' . $year . '-' . $month . '-00T00:00:00Z' );
	}

	/**
	 * @param string|int $value
	 * @return bool can the given value be a month?
	 */
	private function canBeMonth( $value ) {
		if( intval( $value ) > 12 ) {
			return false;
		}
		return true;
	}

}