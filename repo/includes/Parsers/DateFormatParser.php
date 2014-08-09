<?php

namespace Wikibase\Repo\Parsers;

use DataValues\IllegalValueException;
use DataValues\TimeValue;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\StringValueParser;

/**
 * This parser is in essence the inverse operation of Language::sprintfDate.
 *
 * @see Language::sprintfDate
 *
 * @since 0.8.1
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class DateFormatParser extends StringValueParser {

	const FORMAT_NAME = 'date-format';

	const OPT_DATE_FORMAT = 'dateFormat';

	/**
	 * Option for unlocalizing non-canonical digits. Must be an array of strings, mapping canonical
	 * digit characters ("1", "2" and so on, possibly including "." and ",") to localized
	 * characters.
	 */
	const OPT_DIGIT_TRANSFORM_TABLE = 'digitTransformTable';

	/**
	 * Option for localized month names. Should be a two-dimensional array, the first dimension
	 * mapping the month's numbers 1 to 12 to arrays of localized month names, possibly including
	 * full month names, genitive names and abbreviations. Can also be a one-dimensional array of
	 * strings.
	 */
	const OPT_MONTH_NAMES = 'monthNames';

	public function __construct( ParserOptions $options = null ) {
		parent::__construct( $options );

		$this->defaultOption( self::OPT_DATE_FORMAT, 'j F Y' );
		// FIXME: Should not be an option. Options should be trivial, never arrays or objects!
		$this->defaultOption( self::OPT_DIGIT_TRANSFORM_TABLE, null );
		$this->defaultOption( self::OPT_MONTH_NAMES, null );
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
		$pattern = $this->parseDateFormat( $this->getDateFormat() );

		if ( preg_match( '<^\p{Z}*' . $pattern . '$>iu', $value, $matches )
			&& isset( $matches['year'] )
		) {
			$precision = TimeValue::PRECISION_YEAR;
			$time = array( $this->parseFormattedNumber( $matches['year'] ), 0, 0, 0, 0, 0 );

			if ( isset( $matches['month'] ) ) {
				$precision = TimeValue::PRECISION_MONTH;
				$time[1] = $this->findMonthMatch( $matches );
			}

			if ( isset( $matches['day'] ) ) {
				$precision = TimeValue::PRECISION_DAY;
				$time[2] = $this->parseFormattedNumber( $matches['day'] );
			}

			if ( isset( $matches['hour'] ) ) {
				$precision = TimeValue::PRECISION_HOUR;
				$time[3] = $this->parseFormattedNumber( $matches['hour'] );
			}

			if ( isset( $matches['minute'] ) ) {
				$precision = TimeValue::PRECISION_MINUTE;
				$time[4] = $this->parseFormattedNumber( $matches['minute'] );
			}

			if ( isset( $matches['second'] ) ) {
				$precision = TimeValue::PRECISION_SECOND;
				$time[5] = $this->parseFormattedNumber( $matches['second'] );
			}

			$timestamp = vsprintf( '+%04s-%02s-%02sT%02s:%02s:%02sZ', $time );
			try {
				return new TimeValue( $timestamp, 0, 0, 0, $precision, TimeValue::CALENDAR_GREGORIAN );
			} catch ( IllegalValueException $ex ) {
				throw new ParseException( $ex->getMessage(), $value, self::FORMAT_NAME );
			}
		}

		throw new ParseException(
			"Failed to parse $value (" . $this->parseFormattedNumber( $value ) . ')',
			$value,
			self::FORMAT_NAME
		);
	}

	// @codingStandardsIgnoreStart
	/**
	 * @see Language::sprintfDate
	 *
	 * @param string $format A date format, as described in Language::sprintfDate.
	 *
	 * @return string Regular expression
	 */
	private function parseDateFormat( $format ) {
		$length = strlen( $format );
		$numberPattern = '[' . $this->getNumberCharacters() . ']';
		$pattern = '';

		for ( $p = 0; $p < $length; $p++ ) {
			$code = $format[$p];

			if ( $code === 'x' && $p < $length - 1 ) {
				$code .= $format[++$p];
			}

			if ( preg_match( '<^x[ijkmot]$>', $code ) && $p < $length - 1 ) {
				$code .= $format[++$p];
			}

			switch ( $code ) {
				case 'Y':
				case 'xiY':
				case 'xjY':
				case 'xmY':
				case 'xkY':
				case 'xoY':
				case 'xtY':
					$pattern .= '(?P<year>' . $numberPattern . '+)\p{Z}*';
					break;
				case 'F':
				case 'm':
				case 'M':
				case 'n':
				case 'xg':
				case 'xiF':
				case 'xin':
				case 'xiX':
				case 'xjF':
				case 'xjn':
				case 'xjx':
				case 'xmF':
				case 'xmn':
					$pattern .= '(?P<month>' . $numberPattern . '{1,2}'
						. $this->getMonthNamesPattern()
						. ')\p{P}*\p{Z}*';
					break;
				case 'd':
				case 'j':
				case 'xij':
				case 'xjj':
				case 'xjt':
				case 'xmj':
					$pattern .= '(?P<day>' . $numberPattern . '{1,2})\p{P}*\p{Z}*';
					break;
				case 'G':
				case 'H':
					$pattern .= '(?P<hour>' . $numberPattern . '{1,2})\p{Z}*';
					break;
				case 'i':
					$pattern .= '(?P<minute>' . $numberPattern . '{1,2})\p{Z}*';
					break;
				case 's':
					$pattern .= '(?P<second>' . $numberPattern . '{1,2})\p{Z}*';
					break;
				case 'xx':
					$pattern .= 'x';
					break;
				case '\\':
					if ( $p < $length - 1 ) {
						$pattern .= preg_quote( $format[++$p] );
					} else {
						$pattern .= '\\\\';
					}
					break;
				case '"':
					$endQuote = strpos( $format, '"', $p + 1 );
					if ( $endQuote !== false ) {
						$pattern .= preg_quote( substr( $format, $p + 1, $endQuote - $p - 1 ) );
						$p = $endQuote;
					} else {
						$pattern .= '"';
					}
					break;
				case 'xn':
				case 'xN':
					// We can ignore raw and raw toggle when parsing, because we always
				 	// canonical digits.
					break;
				case 'xr':
				case 'xh':
					//
					break;
				default:
					if ( preg_match( '<^\p{P}+$>u', $format[$p] ) ) {
						// Unicode character class "P" is for "Punctuation".
						$pattern .= '\p{P}*';
					} elseif ( preg_match( '<^\p{Z}+$>u', $format[$p] ) ) {
						// Unicode character class "Z" or "Separator" is for whitespace.
						$pattern .= '\p{Z}*';
					} else {
						$pattern .= preg_quote( $format[$p] );
					}
			}
		}

		return $pattern;
	}
	// @codingStandardsIgnoreEnd

	/**
	 * @return string
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
	 * @param string[] $matches
	 *
	 * @return int
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
	 * @return string
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
	private function getNumberCharacters() {
		$numberCharacters = '\d';

		$transformTable = $this->getDigitTransformTable();
		if ( is_array( $transformTable ) ) {
			$numberCharacters .= preg_quote( implode( '', $transformTable ) );
		}

		return $numberCharacters;
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
	 * @return array[]
	 */
	private function getMonthNames() {
		return $this->getOption( self::OPT_MONTH_NAMES ) ?: array();
	}

}
