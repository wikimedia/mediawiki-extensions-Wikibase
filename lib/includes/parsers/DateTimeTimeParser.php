<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use DateTime;
use Exception;
use ValueParsers\CalenderModelParser;
use ValueParsers\ParseException;
use ValueParsers\StringValueParser;
use ValueParsers\TimeParser;

class DateTimeTimeParser extends StringValueParser {

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

		try{
			$result = new DateTime( $value );
			$time = $result->format( self::FORMAT_STRING );
			$time = '+' . str_repeat( '0', 32 - strlen( $time ) ) . $time;
			return $parser->parse( $time );
		}
		catch( Exception $e ) {
			throw new ParseException( $e->getMessage() );
		}
	}

} 