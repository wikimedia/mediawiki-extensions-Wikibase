<?php

namespace Wikibase\Repo\Parsers;

use DataValues\IllegalValueException;
use DataValues\TimeValue;
use ValueParsers\CalendarModelParser;
use ValueParsers\IsoTimestampParser;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\StringValueParser;
use Wikimedia\AtEase\AtEase;

/**
 * This parser is in essence the inverse operation of MediaWiki's Language::sprintfDate.
 *
 * @see \Language::sprintfDate
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class DateFormatParser extends StringValueParser {

	private const FORMAT_NAME = 'date-format';

	public const OPT_DATE_FORMAT = 'dateFormat';

	/**
	 * Option for unlocalizing non-canonical digits. Must be an array of strings, mapping canonical
	 * digit characters ("1", "2" and so on, possibly including "." and ",") to localized
	 * characters.
	 */
	public const OPT_DIGIT_TRANSFORM_TABLE = 'digitTransformTable';

	/**
	 * Option for localized month names. Should be a two-dimensional array, the first dimension
	 * mapping the month's numbers 1 to 12 to arrays of localized month names, possibly including
	 * full month names, genitive names and abbreviations. Can also be a one-dimensional array of
	 * strings.
	 */
	public const OPT_MONTH_NAMES = 'monthNames';

	/**
	 * Option to override the precision auto-detection and set a specific precision. Should be an
	 * integer or string containing one of the TimeValue::PRECISION_... constants.
	 */
	public const OPT_PRECISION = 'precision';

	/** @var IsoTimestampParser */
	private $isoTimestampParser;

	public function __construct( ParserOptions $options = null ) {
		parent::__construct( $options );

		$this->defaultOption( self::OPT_DATE_FORMAT, 'j F Y' );
		// FIXME: Should not be an option. Options should be trivial, never arrays or objects!
		$this->defaultOption( self::OPT_DIGIT_TRANSFORM_TABLE, null );
		$this->defaultOption( self::OPT_MONTH_NAMES, null );
		$this->defaultOption( self::OPT_PRECISION, null );

		$this->isoTimestampParser = new IsoTimestampParser(
			new CalendarModelParser( $this->options ),
			$this->options
		);
	}

	/**
	 * @see StringValueParser::stringParse
	 *
	 * @param string $value
	 *
	 * @throws ParseException
	 * @return TimeValue
	 */
	protected function stringParse( $value ) {
		$date = $this->parseDate( $value );
		$precision = TimeValue::PRECISION_YEAR;
		$time = [ $this->parseFormattedNumber( $date['year'] ), 0, 0, 0, 0, 0 ];

		if ( isset( $date['month'] ) ) {
			$precision = TimeValue::PRECISION_MONTH;
			$time[1] = $this->findMonthMatch( $date );

			if ( isset( $date['day'] ) ) {
				$precision = TimeValue::PRECISION_DAY;
				$time[2] = $this->parseFormattedNumber( $date['day'] );

				if ( isset( $date['hour'] ) ) {
					$precision = TimeValue::PRECISION_HOUR;
					$time[3] = $this->parseFormattedNumber( $date['hour'] );

					if ( isset( $date['minute'] ) ) {
						$precision = TimeValue::PRECISION_MINUTE;
						$time[4] = $this->parseFormattedNumber( $date['minute'] );

						if ( isset( $date['second'] ) ) {
							$precision = TimeValue::PRECISION_SECOND;
							$time[5] = $this->parseFormattedNumber( $date['second'] );
						}
					}
				}
			}
		}

		$option = $this->getOption( self::OPT_PRECISION );
		if ( $option !== null ) {
			if ( !is_int( $option ) && !ctype_digit( $option ) ) {
				throw new ParseException( 'Precision must be an integer' );
			}

			$option = (int)$option;

			// It's impossible to increase the detected precision via option, e.g. from year to month if
			// no month is given. If a day is given it can be increased, relevant for midnight.
			if ( $option <= $precision || $precision >= TimeValue::PRECISION_DAY ) {
				$precision = $option;
			}
		}

		$timestamp = vsprintf( '+%04s-%02s-%02sT%02s:%02s:%02sZ', $time );

		// Use IsoTimestampParser to detect the correct calendar model.
		$iso = $this->isoTimestampParser->parse( $timestamp );

		try {
			// We intentionally do not re-use the precision from IsoTimestampParser here,
			// because it reduces precision for values with zeros in the right-most fields.
			// Our above method of determining the precision is therefore better.
			return new TimeValue( $timestamp, 0, 0, 0, $precision, $iso->getCalendarModel() );
		} catch ( IllegalValueException $ex ) {
			throw new ParseException( $ex->getMessage(), $value, self::FORMAT_NAME );
		}
	}

	// phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded,Squiz.WhiteSpace.FunctionSpacing
	/**
	 * @see Language::sprintfDate
	 *
	 * @param string $format A date format, as described in Language::sprintfDate.
	 *
	 * @return string Regular expression
	 */
	private function parseDateFormat( $format ) {
		$length = strlen( $format );

		$number = $this->getNumberPattern();
		$notFollowedByNumber = '(?!' . $number . ')';
		$optionalPunctuation = '\p{P}*';
		$optionalWhitespace = '\p{Z}*';
		$separation = $notFollowedByNumber . $optionalWhitespace;
		$pattern = '<^' . $optionalWhitespace;

		for ( $p = 0; $p < $length; $p++ ) {
			$code = $format[$p];

			// "x" is used as a prefix for MediaWiki specific, 2- and 3-letter codes.
			if ( $code === 'x' && $p < $length - 1 ) {
				$code .= $format[++$p];

				if ( preg_match( '<^x[ijkmot]$>', $code ) && $p < $length - 1 ) {
					$code .= $format[++$p];
				}
			}

			switch ( $code ) {
				// Year
				case 'o':
				case 'Y':
					$pattern .= '(?P<year>' . $number . '+)' . $separation;
					break;

				// Month
				case 'F':
				case 'M':
				case 'm':
				case 'n':
				case 'xg':
					$pattern .= '(?P<month>' . $number . '{1,2}' . $notFollowedByNumber
						. $this->getMonthNamesPattern() . ')' . $optionalPunctuation
						. $optionalWhitespace;
					break;

				// Day
				case 'd':
				case 'j':
					$pattern .= '(?P<day>' . $number . '{1,2})' . $optionalPunctuation
						. $separation;
					break;

				// Hour
				case 'G':
				case 'H':
					$pattern .= '(?P<hour>' . $number . '{1,2})' . $separation;
					break;

				// Minute
				case 'i':
					$pattern .= '(?P<minute>' . $number . '{1,2})' . $separation;
					break;

				// Second
				case 's':
					$pattern .= '(?P<second>' . $number . '{1,2})' . $separation;
					break;

				// Escaped "x"
				case 'xx':
					$pattern .= 'x';
					break;

				// Escaped character or backslash at the end of the sequence
				case '\\':
					$pattern .= preg_quote( $p < $length - 1 ? $format[++$p] : '\\' );
					break;

				// Quoted sequence
				case '"':
					$endQuote = strpos( $format, '"', $p + 1 );
					if ( $endQuote !== false ) {
						$pattern .= preg_quote( substr( $format, $p + 1, $endQuote - $p - 1 ) );
						$p = $endQuote;
					} else {
						$pattern .= '"';
					}
					break;

				// We can ignore "raw" and "raw toggle" when parsing, because we always accept
				// canonical digits.
				case 'xN':
				case 'xn':
					break;

				// 12-hour format
				case 'A':
				case 'a':
				case 'g':
				case 'h':
				// Full, formatted dates
				case 'c':
				case 'r':
				case 'U':
				// Day of the week
				case 'D':
				case 'l':
				case 'N':
				case 'w':
				// Timezone
				case 'e':
				case 'O':
				case 'P':
				case 'T':
				case 'Z':
				// Daylight saving time ("1" if true)
				case 'I':
				// Leap year ("1" if true)
				case 'L':
				// Number of days in the current month
				case 't':
				case 'xit':
				case 'xjt':
				// Week number
				case 'W':
				// "Hebrew" and "Roman" modifiers
				case 'xh':
				case 'xr':
				// 2-digit year
				case 'y':
				case 'xiy':
				// Day of the year
				case 'z':
				case 'xiz':
				// Day, month and year in incompatible calendar models (Hebrew, Iranian, and others)
				case 'xiF':
				case 'xij':
				case 'xin':
				case 'xiY':
				case 'xjF':
				case 'xjj':
				case 'xjn':
				case 'xjx':
				case 'xjY':
				case 'xkY':
				case 'xmF':
				case 'xmj':
				case 'xmn':
				case 'xmY':
				case 'xoY':
				case 'xtY':
					throw new ParseException( 'Unsupported date format "' . $code . '"' );

				// Character with no meaning
				default:
					if ( preg_match( '<^' . $optionalPunctuation . '$>u', $format[$p] ) ) {
						$pattern .= $optionalPunctuation;
					} elseif ( preg_match( '<^' . $optionalWhitespace . '$>u', $format[$p] ) ) {
						$pattern .= $optionalWhitespace;
					} else {
						$pattern .= preg_quote( $format[$p] );
					}
			}
		}

		return $pattern . '$>iu';
	}
	// phpcs:enable

	/**
	 * @return string Partial regular expression
	 */
	private function getNumberPattern() {
		$pattern = '[\d';

		$transformTable = $this->getDigitTransformTable();
		if ( is_array( $transformTable ) ) {
			$pattern .= preg_quote( implode( '', $transformTable ) );
		}

		return $pattern . ']';
	}

	/**
	 * @return string Partial regular expression
	 */
	private function getMonthNamesPattern() {
		$pattern = '';

		foreach ( $this->getMonthNames() as $i => $monthNames ) {
			$pattern .= '|(?P<month' . $i . '>'
				. implode( '|', array_map( 'preg_quote', (array)$monthNames ) )
				. ')';
		}

		return $pattern;
	}

	/**
	 * @param string $input
	 *
	 * @throws ParseException
	 * @return string[] Guaranteed to have the "year" key, optionally followed by more elements.
	 *  Guaranteed to be continuous, e.g. "year" and "day" with no "month" is illegal.
	 */
	private function parseDate( $input ) {
		$pattern = $this->parseDateFormat( $this->getDateFormat() );

		AtEase::suppressWarnings();
		$success = preg_match( $pattern, $input, $matches );
		AtEase::restoreWarnings();

		if ( !$success ) {
			throw new ParseException(
				$success === false
					? 'Illegal date format "' . $this->getDateFormat() . '"'
					: 'Failed to parse "' . $input . '"',
				$input,
				self::FORMAT_NAME
			);
		}

		if ( !isset( $matches['year'] )
			|| isset( $matches['day'] ) && !isset( $matches['month'] )
			|| isset( $matches['hour'] ) && !isset( $matches['day'] )
			|| isset( $matches['minute'] ) && !isset( $matches['hour'] )
			|| isset( $matches['second'] ) && !isset( $matches['minute'] )
		) {
			throw new ParseException( 'Non-continuous date format', $input, self::FORMAT_NAME );
		}

		return $matches;
	}

	/**
	 * @param string[] $matches
	 *
	 * @return int|string
	 */
	private function findMonthMatch( $matches ) {
		for ( $i = 1; $i <= 12; $i++ ) {
			if ( !empty( $matches['month' . $i] ) ) {
				return $i;
			}
		}

		return $this->parseFormattedNumber( $matches['month'] );
	}

	/**
	 * @param string $number
	 *
	 * @return string Canonical number
	 */
	private function parseFormattedNumber( $number ) {
		$transformTable = $this->getDigitTransformTable();

		if ( is_array( $transformTable ) ) {
			// Eliminate empty array values (bug T66347).
			$transformTable = array_filter( $transformTable );
			$number = strtr( $number, array_flip( $transformTable ) );
		}

		return $number;
	}

	/**
	 * @return string
	 */
	private function getDateFormat() {
		return $this->getOption( self::OPT_DATE_FORMAT );
	}

	/**
	 * @return string[]|null
	 */
	private function getDigitTransformTable() {
		return $this->getOption( self::OPT_DIGIT_TRANSFORM_TABLE );
	}

	/**
	 * @return array[]|string[]
	 */
	private function getMonthNames() {
		return $this->getOption( self::OPT_MONTH_NAMES ) ?: [];
	}

}
