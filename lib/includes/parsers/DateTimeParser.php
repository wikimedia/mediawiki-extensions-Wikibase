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

	/**
	 * @var EraParser
	 */
	private $eraParser;

	/**
	 * @var string 4242 placeholder used while parsing dates with greater than 4 digit years
	 */
	private $yearPlaceholder = '4242';

	public function __construct( EraParser $eraParser, ParserOptions $options = null ) {
		parent::__construct( $options );
		$this->monthUnlocaliser = new MonthNameUnlocalizer();
		$this->eraParser = $eraParser;
	}

	/**
	 * Parses the provided string
	 *
	 * @param string $value in a format as specified by the PHP DateTime object
	 *       there are exceptions as we can handel 5+ digit dates
	 *
	 * @throws ParseException
	 * @return TimeValue
	 */
	protected function stringParse( $value ) {
		$calendarModelParser = new CalendarModelParser();
		$options = $this->getOptions();

		//Holder when large years are found
		$largeYear = null;

		try{
			$value = $this->getValueWithFixedYearLengths(
				$this->getValueWithFixedSeperators(
					$this->monthUnlocaliser->unlocalize(
						trim( $value ),
						$options->getOption( ValueParser::OPT_LANG ),
						new ParserOptions()
					)
				)
			);

			//PHP's DateTime object also cant handel larger than 4 digit years
			//e.g. 1 June 202020
			if( preg_match( '/^(.*[^\d]|)(\d{5,})(.*|)$/', $value, $matches ) ) {
				$largeYear = $matches[2];
				$value = $matches[1] . $this->yearPlaceholder . $matches[3];
			}

			list( $sign, $cleanResult ) = $this->eraParser->parse( $value );

			//Parse using the DateTime object (this will allow us to format the date in a nicer way)
			$dateTime = new DateTime( $cleanResult );
			$timeString = $sign . $dateTime->format( 'Y-m-d\TH:i:s\Z' );

			if( $largeYear !== null ) {
				$timeString = preg_replace( '/' . $this->yearPlaceholder . '/', $largeYear, $timeString, 1 );
			}

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
	private function getValueWithFixedSeperators( $value ) {
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
		if( preg_match( '/^(\d+)[^\d]?(\d+)[^\d]?(\d+)$/', $value, $dateParts ) ) {
			if( $dateParts[1] > 31 && $dateParts[3] <= 31 ) {
				// the year looks like it is at the front
				if( preg_match( '/^(\d{1,3})([^\d].*)$/', $value, $matches ) ) {
					$value = str_pad( $matches[1], 4, '0', STR_PAD_LEFT ) . $matches[2];
				}
			} else {
				// presume the year is at the back
				if( preg_match( '/^(.*[^\d])(\d{1,3})$/', $value, $matches ) ) {
					$value = $matches[1] . str_pad( $matches[2], 4, '0', STR_PAD_LEFT );
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
