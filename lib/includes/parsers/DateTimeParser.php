<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use DateTime;
use Exception;
use ValueParsers\CalenderModelParser;
use ValueParsers\ParseException;
use ValueParsers\StringValueParser;
use ValueParsers\ParserOptions;

/**
 * Time Parser using the DateTime object
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class DateTimeParser extends StringValueParser {

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
		global $wgLang;
		$parser = new \ValueParsers\TimeParser( new CalenderModelParser(), $this->getOptions() );
		$unlocaliser = new MonthNameUnlocalizer();
		$options = new ParserOptions();
		try{
			$value = $unlocaliser->unlocalize( $value, $wgLang->getCode(),$options );
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