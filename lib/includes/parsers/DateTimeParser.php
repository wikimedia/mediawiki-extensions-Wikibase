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
			$value = $this->getValueWithFixedYearLengths(
				$this->getValueWithFixedSeparators(
					$this->monthUnlocaliser->unlocalize(
						trim( $value ),
						$options->getOption( ValueParser::OPT_LANG ),
						new ParserOptions()
					)
				)
			);

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

	/**
	 * PHP's DateTime object does not accept spaces as separators between year, month and day,
	 * e.g. dates like 20 12 2012, but we want to support them.
	 * See http://de1.php.net/manual/en/datetime.formats.date.php
	 *
	 * @param string $value
	 *
	 * @return mixed
	 */
	private function getValueWithFixedSeparators( $value ) {
		return preg_replace( '/\s+/', '.', $value );
	}

	/**
	 * PHP's DateTime object also cant handel smaller than 4 digit years
	 * e.g. instead of 12 it needs 0012 etc.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	private function getValueWithFixedYearLengths( $value ) {
		if( preg_match( '/^(\d+)([^\d])(\d+)([^\d])(\d+)$/', $value, $dateParts ) ) {
			if( $dateParts[1] > 31 && $dateParts[5] <= 31 ) {
				// the year looks like it is at the front
				if( strlen( $dateParts[1] ) < 4 ) {
					$value = str_pad( $dateParts[1], 4, '0', STR_PAD_LEFT )
						. $dateParts[2] . $dateParts[3] . $dateParts[4] . $dateParts[5];
				}
			} else {
				// presume the year is at the back
				if( strlen( $dateParts[5] ) < 4 ) {
					$value = $dateParts[1] . $dateParts[2] . $dateParts[3] . $dateParts[4]
						. str_pad( $dateParts[5], 4, '0', STR_PAD_LEFT );
				}
			}
		} else {
			if( preg_match( '/^(.*[^\d])(\d{1,3})$/', $value, $matches ) ) {
				$value = $matches[1] . str_pad( $matches[2], 4, '0', STR_PAD_LEFT );
			}
		}
		return $value;
	}

}