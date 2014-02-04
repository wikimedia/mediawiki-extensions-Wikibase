<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use ValueParsers\CalenderModelParser;
use ValueParsers\ParseException;
use ValueParsers\StringValueParser;
use ValueParsers\TimeParser;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class YearTimeParser extends StringValueParser {

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
		$value = trim( $value );
		if( preg_match( '/\d+/', $value, $matches ) ) {
			return $this->getTimeFromYear( $value );
		}
		throw new ParseException( 'Failed to parse year' );
	}

	/**
	 * @param string $year
	 * @return TimeValue
	 */
	private function getTimeFromYear( $year ) {
		$timeParser = new TimeParser( new CalenderModelParser(), $this->getOptions() );
		return $timeParser->parse( '+' . $year . '-00-00T00:00:00Z' );
	}

}