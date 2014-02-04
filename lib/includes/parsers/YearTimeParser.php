<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
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
		$value = trim( $value );
		if( preg_match( '/^(\+|\-)?(\d+)$/', $value, $matches ) ) {
			return $this->getTimeFromYear( $value );
		}
		throw new ParseException( 'Failed to parse year: ' . $value );
	}

	/**
	 * @param string $year
	 * @return TimeValue
	 */
	private function getTimeFromYear( $year ) {
		if( !preg_match( '/^(\+|\-)/', $year ) ) {
			$year = '+' . $year;
		}
		return $this->timeValueTimeParser->parse( $year . '-00-00T00:00:00Z' );
	}

}