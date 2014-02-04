<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use Language;
use ValueParsers\CalenderModelParser;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\StringValueParser;
use ValueParsers\ValueParser;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * @todo move me to DataValues-time
 */
class YearMonthTimeParser extends StringValueParser {

	/**
	 * @var Language
	 */
	protected $lang;

	/**
	 * @see StringValueParser::__construct
	 */
	public function __construct( ParserOptions $options = null ) {
		if( is_null( $options ) ) {
			$options = new ParserOptions();
		}
		$options->defaultOption( TimeParser::OPT_CALENDER, TimeParser::OPT_CALENDER_GREGORIAN );
		$options->defaultOption( TimeParser::OPT_PRECISION, TimeParser::OPT_PRECISION_NONE );

		parent::__construct( $options );
		$this->lang = Language::factory( $this->getOptions()->getOption( ValueParser::OPT_LANG ) );
	}

	/**
	 * Parses the provided string and returns the result.
	 *
	 * @param string $value
	 *
	 * @throws ParseException
	 * @return TimeValue
	 */
	protected function stringParse( $value ) {
		//REGEX Matches Year and month separated by a separator, \p{L} matches letters outside of the ASCII range
		if( !preg_match( '/^([\d\p{L}]+)[\/\-\s\.\,]([\d\p{L}]+)$/', trim( $value ), $matches ) ) {
			throw new ParseException( 'Failed to parse year and month: ' . $value );
		}
		list( ,$a, $b ) = $matches;

		$aIsInt = preg_match( '/^\d+$/', $a );
		$bIsInt = preg_match( '/^\d+$/', $b );

		if( $aIsInt && $bIsInt ) {
			$parsed = $this->parseYearMonthTwoInts( $a, $b );
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

			$parsed =  $this->parseYearMonth( $year, $month );
			if( $parsed ) {
				return $parsed;
			}
		}

		throw new ParseException( 'Failed to parse year and month: ' . $value );
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
	private function parseYearMonthTwoInts( $a, $b  ) {
		if( !preg_match( '/^\d+$/', $a ) || !preg_match( '/^\d+$/', $b ) ) {
			return false;
		}

		if( !$this->canBeMonth( $a ) && $this->canBeMonth( $b ) ) {
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
	private function parseYearMonth( $year, $month ) {
		$names = $this->lang->getMonthNamesArray();
		for ( $i = 1; $i <= 12; $i++ ) {
			if( strcasecmp( $names[$i], $month ) === 0 ) {
				return $this->getTimeFromYearMonth( $year, $i );
			}
		}
		$nameAbbrevs = $this->lang->getMonthAbbreviationsArray();
		for ( $i = 1; $i <= 12; $i++ ) {
			if( strcasecmp( $nameAbbrevs[$i], $month ) === 0 ) {
				return $this->getTimeFromYearMonth( $year, $i );
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
		$timeParser = new \ValueParsers\TimeParser( new CalenderModelParser(), $this->getOptions() );
		return $timeParser->parse( sprintf( '+%d-%02d-00T00:00:00Z', $year, $month ) );
	}

	/**
	 * @param string|int $value
	 * @return bool can the given value be a month?
	 */
	private function canBeMonth( $value ) {
		$value = intval( $value );
		return $value >= 0 && $value <= 12;
	}

}