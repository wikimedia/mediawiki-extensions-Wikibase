<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use ValueParsers\CalenderModelParser;
use ValueParsers\ParseException;
use ValueParsers\StringValueParser;
use ValueParsers\TimeParser;

class DispatchingTimeParser extends StringValueParser {

	/**
	 * Parses the provided string and returns the result.
	 *
	 * @param string $value
	 *
	 * @throws ParseException
	 * @return TimeValue
	 */
	protected function stringParse( $value ) {
		foreach ( $this->getParsers() as $parser ) {
			try {
				return $parser->parse( $value );
			}
			catch ( ParseException $parseException ) {
				continue;
			}
		}

		throw new ParseException( 'The format of the time could not be determined. Parsing failed.' );
	}

	/**
	 * @return  StringValueParser[]
	 */
	protected function getParsers() {
		$parsers = array();

		$parsers[] = new TimeParser( new CalenderModelParser(), $this->options );
		$parsers[] = new MWTimeIsoParser();
		$parsers[] = new DateTimeTimeParser();
		$parsers[] = new StrToTimeTimeParser();

		return $parsers;
	}

}