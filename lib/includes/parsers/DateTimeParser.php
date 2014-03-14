<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use DateTime;
use Exception;
use ValueParsers\CalendarModelParser;
use ValueParsers\ParseException;
use ValueParsers\StringValueParser;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;

/**
 * Time Parser using the DateTime object
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * @todo move me to DataValues-time
 */
class DateTimeParser extends StringValueParser {

	/**
	 * @var MonthNameUnlocalizer
	 */
	private $monthUnlocaliser;

	public function __construct( ParserOptions $options = null ) {
		parent::__construct( $options );
		$this->monthUnlocaliser = new MonthNameUnlocalizer();
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
		$calendarModelParser = new CalendarModelParser();
		$options = $this->getOptions();
		try{
			$value = $this->monthUnlocaliser->unlocalize(
				$value,
				$options->getOption( ValueParser::OPT_LANG ),
				new ParserOptions()
			);

			//WTF PHP FAIL: Apparently PHP doesn't like spaces as separators in dates, so replace with a '-'
			//              See http://de1.php.net/manual/en/datetime.formats.date.php
			$value = preg_replace( '/\s+/', '.', trim( $value ) );

			//Parse using the DateTime object (this will allow us to format the date in a nicer way)
			//TODO try to match and remove BCE etc. before putting the value into the DateTime object to get - dates!
			$dateTime = new DateTime( $value );
			$timeString = '+' . $dateTime->format( 'Y-m-d\TH:i:s\Z' );

			//Pass the reformatted string into a base parser that parses this +/-Y-m-d\TH:i:s\Z format with a precision
			$valueParser = new \ValueParsers\TimeParser( $calendarModelParser, $options );
			return $valueParser->parse( $timeString );

		}
		catch( Exception $exception ) {
			throw new ParseException( $exception->getMessage() );
		}
	}

}