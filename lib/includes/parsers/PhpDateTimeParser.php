<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use DateTime;
use Exception;
use ValueParsers\CalendarModelParser;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\StringValueParser;
use ValueParsers\ValueParser;

/**
 * Time parser using PHP's DateTime object. Since the behavior of PHP's parser can be quite odd
 * (for example, it pads missing elements with the current date and does actual calculations such as
 * parsing "2015-00-00" as "2014-12-30") this parser should only be used as a fallback.
 *
 * This class implements heuristics to guess which sequence of digits in the input string
 * represents the year. The following criteria are used to identify a year:
 *
 * - the first number longer than 2 digits or bigger than 59.
 * - the first number in the string, if it is large than 31.
 * - the third of three space-separated parts at the beginning of the input, if it is a number.
 * - the third of three space-separated parts, if it is a number.
 * - the last number in the string otherwise.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Thiemo Mättig
 *
 * @todo move me to DataValues-time
 */
class PhpDateTimeParser extends StringValueParser {

	const FORMAT_NAME = 'datetime';

	/**
	 * @var MonthNameUnlocalizer
	 */
	private $monthUnlocalizer;

	/**
	 * @var EraParser
	 */
	private $eraParser;

	public function __construct( EraParser $eraParser, ParserOptions $options = null ) {
		parent::__construct( $options );

		$languageCode = $options->getOption( ValueParser::OPT_LANG );
		$this->monthUnlocalizer = new MonthNameUnlocalizer( $languageCode );
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
		$rawValue = $value;

		$calendarModelParser = new CalendarModelParser();
		$options = $this->getOptions();

		try {
			list( $sign, $value ) = $this->eraParser->parse( $value );

			$value = trim( $value );
			$value = $this->monthUnlocalizer->unlocalize( $value );
			$year = $this->fetchAndNormalizeYear( $value );
			$value = $this->getValueWithFixedSeparators( $value );

			$this->validateDateTimeInput( $value );

			// Parse using the DateTime object (this will allow us to format the date in a nicer way)
			$dateTime = new DateTime( $value );

			// Fail if the DateTime object does calculations like changing 2015-00-00 to 2014-12-30.
			if ( $year !== null && $dateTime->format( 'Y' ) !== substr( $year, -4 ) ) {
				throw new ParseException( $value . ' is not a valid date.' );
			}

			if ( $year !== null && strlen( $year ) > 4 ) {
				$timeString = $sign . $year . $dateTime->format( '-m-d\TH:i:s\Z' );
			} else {
				$timeString = $sign . $dateTime->format( 'Y-m-d\TH:i:s\Z' );
			}

			// Pass the reformatted string into a base parser that parses this +/-Y-m-d\TH:i:s\Z format with a precision
			$valueParser = new \ValueParsers\TimeParser( $calendarModelParser, $options );
			return $valueParser->parse( $timeString );
		} catch ( Exception $exception ) {
			throw new ParseException( $exception->getMessage(), $rawValue, self::FORMAT_NAME );
		}
	}

	/**
	 * @param string $value
	 *
	 * @throws ParseException
	 */
	private function validateDateTimeInput( $value ) {
		// we don't support input of non-digits only, such as 'x'.
		if ( !preg_match( '/\d/', $value ) ) {
			throw new ParseException( $value . ' is not a valid date.' );
		}

		// @todo i18n support for these exceptions
		// we don't support dates in format of year + timezone
		if ( preg_match( '/^\d{1,7}(\+\d*|\D*)$/', $value ) ) {
			throw new ParseException( $value . ' is not a valid date.' );
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
		return preg_replace( '/(?<=\d)[.\s]\s*/', '.', $value );
	}

	/**
	 * Tries to find and pad the sequence of digits in $value that represents the year.
	 * Refer to the class level documentation for a description of the heuristics used.
	 *
	 * @param string &$value A time value string, possibly containing a year. If found, the year in
	 * the string will be cut and padded to exactly 4 digits.
	 *
	 * @return string|null The full year, if found, not cut but padded to at least 4 digits.
	 */
	private function fetchAndNormalizeYear( &$value ) {
		// NOTE: when changing the regex matching below, keep the class level
		// documentation of the extraction heuristics up to date!

		if ( !preg_match(
			// Check if the string contains a number longer than 2 digits or bigger than 59.
			'/(?<!\d)('       //can not be prepended by a digit
				. '\d{3,}|'   //any number longer than 2 digits, or
				. '[6-9]\d'   //any number bigger than 59
				. ')(?!\d)/', //can not be followed by a digit
			$value,
			$matches,
			PREG_OFFSET_CAPTURE
		) && !preg_match(
			// Check if the first number in the string is bigger than 31.
			'/^\D*(3[2-9]|[4-9]\d)/',
			$value,
			$matches,
			PREG_OFFSET_CAPTURE
		) && !preg_match(
			// Check if the string starts with three space-separated parts or three numbers.
			'/^(?:'
				. '\S+\s+\S+\s+|' //e.g. "July<SPACE>4th<SPACE>", or
				. '\d+\D+\d+\D+'  //e.g. "4.7."
				. ')(\d+)/',      //followed by a number
			$value,
			$matches,
			PREG_OFFSET_CAPTURE
		) && !preg_match(
			// Check if the string ends with a number.
			'/(\d+)\D*$/',
			$value,
			$matches,
			PREG_OFFSET_CAPTURE
		) ) {
			return null;
		}

		$year = $matches[1][0];
		$index = $matches[1][1];
		$length = strlen( $year );

		// Trim irrelevant leading zeros.
		$year = ltrim( $year, '0' );

		// Pad to at least 4 digits.
		$year = str_pad( $year, 4, '0', STR_PAD_LEFT );

		// Manipulate the value to have an exactly 4-digit year. Crucial for PHP's DateTime object.
		$value = substr_replace( $value, substr( $year, -4 ), $index, $length );

		return $year;
	}

}
