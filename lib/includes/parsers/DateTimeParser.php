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
		$valueParser = new \ValueParsers\TimeParser( new CalenderModelParser(), $this->getOptions() );
		$options = new ParserOptions();
		try{
			$value = $this->monthUnlocaliser->unlocalize(
				$value,
				$this->getOptions()->getOption( ValueParser::OPT_LANG ),
				$options
			);
			//Parse using the DateTime object (this will allow us to format the date in a nicer way)
			$result = new DateTime( $value );
			//Pass the reformatted string into our base parse that parses this +/-Y-m-d\TH:i:s\Z format
			return $valueParser->parse( '+' . $result->format( 'Y-m-d\TH:i:s\Z' ) );
		}
		catch( Exception $exception ) {
			throw new ParseException( $exception->getMessage() );
		}
	}

}