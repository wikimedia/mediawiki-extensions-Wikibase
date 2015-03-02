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
			$value = $this->getValueWithFixedSeparators( $value );
			$year = $this->fetchAndNormalizeYear( $value );

			$this->validateDateTimeInput( $value );

			// Parse using the DateTime object (this will allow us to format the date in a nicer way)
			$dateTime = new DateTime( $value );

			// Fail if the DateTime object does calculations like changing 2015-00-00 to 2014-12-30.
			if ( $year !== null
				&& $dateTime->format( 'Y' ) !== str_pad( substr( $year, -4 ), 4, '0', STR_PAD_LEFT )
			) {
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
	 * @param string &$value A time value string, possibly containing a year. If found, the year in
	 * the string will be cut and padded to exactly 4 digits.
	 *
	 * @return string|null The full year, if found, not cut but padded to at least 4 digits.
	 */
	private function fetchAndNormalizeYear( &$value ) {
		// Any number longer than 2 digits or bigger than 31 must be the year. Otherwise assume the
		// number at the end of the string is the year.
		if ( !preg_match(
			'/(?<!\d)(?:'           //can not be prepended by a digit
				. '\d{3,}|'         //any number with more than 2 digits, or
				. '3[2-9]|[4-9]\d|' //any number larger than 31, or
				. '\d+$'            //any number at the end of the string
				. ')(?!\d)/',       //can not be followed by a digit
			$value,
			$matches,
			PREG_OFFSET_CAPTURE
		) ) {
			return null;
		}

		$year = $matches[0][0];
		// Trim irrelevant leading zeros.
		$year = ltrim( $year, '0' );
		// Pad to at least 4 digits.
		$year = str_pad( $year, 4, '0', STR_PAD_LEFT );

		// Manipulate the value to have an exactly 4-digit year. Crucial for PHP's DateTime object.
		$value = substr_replace( $value, substr( $year, -4 ), $matches[0][1], strlen( $matches[0][0] ) );

		return $year;
	}

}
