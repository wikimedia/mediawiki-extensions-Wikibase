<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use DateTime;
use Exception;
use ValueParsers\CalenderModelParser;
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
		$calanderModelParser = new CalenderModelParser();
		try{
			$value = $this->monthUnlocaliser->unlocalize(
				$value,
				$this->getOptions()->getOption( ValueParser::OPT_LANG ),
				new ParserOptions()
			);

			//Parse using the DateTime object (this will allow us to format the date in a nicer way)
			$dateTime = new DateTime( $value );
			$timeString = '+' . $dateTime->format( 'Y-m-d\TH:i:s\Z' );

			if(
				$this->getOptions()->hasOption( TimeParser::OPT_PRECISION ) &&
				$this->getOptions()->hasOption( TimeParser::OPT_CALENDER ) &&
				is_int( $this->getOption( TimeParser::OPT_PRECISION ) )
			) {
				return new TimeValue(
					$timeString,
					0, 0, 0,
					$this->getOption( TimeParser::OPT_PRECISION ),
					$calanderModelParser->parse( $this->getOption( TimeParser::OPT_CALENDER ) )
				);
			}

			//Pass the reformatted string into a base parser that parses this +/-Y-m-d\TH:i:s\Z format with a precision
			$valueParser = new \ValueParsers\TimeParser( $calanderModelParser, $this->getOptions() );
			return $valueParser->parse( $timeString );

		}
		catch( Exception $exception ) {
			throw new ParseException( $exception->getMessage() );
		}
	}

}