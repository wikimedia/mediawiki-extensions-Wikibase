<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use ValueParsers\CalenderModelParser;
use ValueParsers\ParseException;
use ValueParsers\StringValueParser;
use ValueParsers\TimeParser;

class StrToTimeTimeParser extends StringValueParser {

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
		$parser = new TimeParser( new CalenderModelParser(), $this->getOptions() );

		$result = strtotime( $value );
		if( $result ) {
			$time = date( self::FORMAT_STRING, $result );
			$time = '+' . str_repeat( '0', 32 - strlen( $time ) ) . $time;
			return $parser->parse( $time );
		}

		throw new ParseException( 'Failed to parse value' );
	}

} 