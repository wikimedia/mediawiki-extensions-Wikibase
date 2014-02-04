<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use ValueParsers\CalenderModelParser;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\StringValueParser;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * @todo move me to DataValues-time
 */
class YearTimeParser extends StringValueParser {

	/**
	 * @var \ValueParsers\TimeParser
	 */
	protected $timeValueTimeParser;

	/**
	 * @param ParserOptions $options
	 */
	public function __construct( ParserOptions $options = null ) {
		parent::__construct( $options );
		$this->timeValueTimeParser = new \ValueParsers\TimeParser( new CalenderModelParser(), $this->getOptions() );
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
		if( preg_match( '/^[+-]?(\d+)$/', $value, $matches ) || preg_match( '/^(\d+)\s*BCE?$/i', $value, $matches ) ) {
			return $this->getTimeFromYear( $value );
		}
		throw new ParseException( 'Failed to parse year: ' . $value );
	}

	/**
	 * @param string $year
	 * @return TimeValue
	 */
	private function getTimeFromYear( $year ) {
		$sign = $this->getSignFromYear( $year );
		$year = $this->cleanYear( $year );
		return $this->timeValueTimeParser->parse( $sign . $year . '-00-00T00:00:00Z' );
	}

	/**
	 * @param string $year
	 * @return string the sign + or -
	 */
	private function getSignFromYear( $year ) {
		$char1 = substr( $year, 0, 1 );
		if( $char1 === '-' || $char1 === '+' ) {
			return $char1;
		}
		if( preg_match( '/^(\d+)\s*BCE?$/i', $year, $matches ) ) {
			return '-';
		}
		return '+';
	}

	/**
	 * @param string $year
	 *
	 * @return string
	 */
	private function cleanYear( $year ) {
		preg_match( '/^[\+\-]?(\d+)(\s*BCE?)?$/i', $year, $matches );
		return $matches[1];
	}

}