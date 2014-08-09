<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use Language;
use ValueFormatters\TimeFormatter;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\StringValueParser;
use ValueParsers\ValueParser;

/**
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class MwDateFormatParser extends StringValueParser {

	const OPT_FORMAT = 'format';
	const OPT_STRICT = 'strict';

	/**
	 * @var Language
	 */
	private $language;

	public function __construct( ParserOptions $options = null ) {
		if ( $options === null ) {
			$options = new ParserOptions();
		}

		$options->defaultOption( self::OPT_FORMAT, 'j F Y' );
		$options->defaultOption( self::OPT_STRICT, false );

		parent::__construct( $options );
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
		$format = $this->options->getOption( self::OPT_FORMAT );
		if ( $this->options->getOption( self::OPT_STRICT ) ) {
			$numberCharacters = '\d';
			$transformTable = $this->getLanguage()->digitTransformTable();
			if ( is_array( $transformTable ) ) {
				$numberCharacters .= preg_quote( implode( '', $transformTable ), '/' );
			}
		} else {
			$numberCharacters = '\p{N}';
		}
		$pattern = '';

		$formatLength = strlen( $format );
		for ( $p = 0; $p < $formatLength; $p++ ) {
			$code = $format[$p];

			if ( $code === 'x' && $p < $formatLength - 1 ) {
				$code .= $format[++$p];
			}

			if ( preg_match( '/^x[ijkmot]$/', $code ) && $p < $formatLength - 1 ) {
				$code .= $format[++$p];
			}

			switch ( $code ) {
				case 'Y':
					$pattern .= '(?P<year>[' . $numberCharacters . ']+)\p{Z}*';
					break;
				case 'F':
				case 'm':
				case 'M':
				case 'n':
				case 'xg':
					$pattern .= '(?P<month>';
					if ( $this->options->getOption( self::OPT_STRICT ) ) {
						$pattern .= '[' . $numberCharacters . ']{1,2}';
						foreach ( $this->getMonthsNames() as $i => $monthNames ) {
							$pattern .= '|(?P<month' . $i . '>' . implode( '|', array_map(
								function( $monthName ) {
									return preg_quote( $monthName, '/' );
								}, $monthNames
							) ) . ')';
						}
						$pattern .= '\p{Z}*';
					} else {
						$pattern .= '[' . $numberCharacters . ']{0,2}[^' . $numberCharacters . ']*';
					}
					$pattern .= ')';
					break;
				case 'd':
				case 'j':
					$pattern .= '(?P<day>[' . $numberCharacters . ']{1,2})\p{P}*\p{Z}*';
					break;
				case 'G':
				case 'H':
					$pattern .= '(?P<hour>[' . $numberCharacters . ']{1,2})\p{Z}*';
					break;
				case 'i':
					$pattern .= '(?P<minute>[' . $numberCharacters . ']{1,2})\p{Z}*';
					break;
				case 's':
					$pattern .= '(?P<second>[' . $numberCharacters . ']{1,2})\p{Z}*';
					break;
				case '\\':
					if ( $p < $formatLength - 1 ) {
						$pattern .= preg_quote( $format[++$p], '/' );
					} else {
						$pattern .= '\\';
					}
					break;
				case '"':
					$endQuote = strpos( $format, '"', $p + 1 );
					if ( $endQuote !== false ) {
						$pattern .= preg_quote( substr( $format, $p + 1, $endQuote - $p - 1 ), '/' );
						$p = $endQuote;
					} else {
						$pattern .= '"';
					}
					break;
				case 'xn':
				case 'xN':
					// We can ignore raw and raw toggle when parsing
					break;
				default:
					if ( preg_match( '/^\p{P}+$/u', $format[$p] ) ) {
						$pattern .= '\p{P}*';
					} elseif ( preg_match( '/^\p{Z}+$/u', $format[$p] ) ) {
						$pattern .= '\p{Z}*';
					} else {
						$pattern .= preg_quote( $format[$p], '/' );
					}
			}
		}

		$isMatch = preg_match( '/^\p{Z}*' . $pattern . '$/iu', $value, $matches );
		//if($isMatch){var_dump($matches);die();}
		if ( $isMatch && isset( $matches['year'] ) ) {
			$precision = TimeValue::PRECISION_YEAR;
			$time = array( $this->normalizeNumber( $matches['year'] ), 0, 0, 0, 0, 0 );

			//if($value==='05:42, 4. Mär. 1201'){var_dump($matches);die();}
			if ( isset( $matches['month'] ) ) {
				$precision = TimeValue::PRECISION_MONTH;
				$time[1] = $this->findMonthMatch( $matches );
			}

			if ( isset( $matches['day'] ) ) {
				$precision = TimeValue::PRECISION_DAY;
				$time[2] = $this->normalizeNumber( $matches['day'] );
			}

			if ( isset( $matches['hour'] ) ) {
				$precision = TimeValue::PRECISION_HOUR;
				$time[3] = $this->normalizeNumber( $matches['hour'] );
			}

			if ( isset( $matches['minute'] ) ) {
				$precision = TimeValue::PRECISION_MINUTE;
				$time[4] = $this->normalizeNumber( $matches['minute'] );
			}

			if ( isset( $matches['second'] ) ) {
				$precision = TimeValue::PRECISION_SECOND;
				$time[5] = $this->normalizeNumber( $matches['second'] );
			}

			$time = vsprintf( '%+.0f-%02d-%02dT%02d:%02d:%02dZ', $time );
			//if($month[0]==='M'&&$i===3){var_dump($month,$regex,preg_match( $regex, $month ));die();}
			return new TimeValue( $time, 0, 0, 0, $precision, TimeFormatter::CALENDAR_GREGORIAN );
		}

		throw new ParseException( "Failed to parse $value (" . $this->parseFormattedNumber( $value ) . ")", $value );
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

		return $this->normalizeMonth( $matches['month'] );
	}

	/**
	 * @param string $month
	 *
	 * @return int
	 */
	private function normalizeMonth( $month ) {
		foreach ( $this->getMonthsNames() as $i => $monthNames ) {
			$pattern = '/^\p{Z}*('
				. implode( '|', array_map(
					function( $monthName ) {
						return preg_quote( preg_replace( '/\p{P}+$/u', '', $monthName ), '/' );
					}, $monthNames
				) )
				. ')[\p{P}\p{Z}]*$/iu';

			if ( preg_match( $pattern, $month ) ) {
				return $i;
			}
		}

		return $this->normalizeNumber( $month );
	}

	/**
	 * @param string $number
	 *
	 * @return double
	 */
	private function normalizeNumber( $number ) {
		$number = $this->parseFormattedNumber( $number );
		return doubleval( preg_replace( '/\D+/s', '', $number ) );
	}

	private function parseFormattedNumber( $number ) {
		$transformTable = $this->getLanguage()->digitTransformTable();

		if ( is_array( $transformTable ) ) {
			// Eliminate empty array values (bug 64347)
			$transformTable = array_filter( $transformTable );
			$number = strtr( $number, array_flip( $transformTable ) );
		}

		return $number;
	}

	/**
	 * @return array[]
	 */
	private function getMonthsNames() {
		$language = $this->getLanguage();
		$months = array();

		for ( $i = 1; $i <= 12; $i++ ) {
			$months[$i] = array(
				$this->trim( $language->getMonthName( $i ) ),
				$this->trim( $language->getMonthNameGen( $i ) ),
				$this->trim( $language->getMonthAbbreviation( $i ) ),
			);
		}

		return $months;
	}

	private function trim( $string ) {
		return preg_replace( '/^\p{Z}|\p{Z}$/u', '',$string );
	}

	private function getLanguage() {
		if ( $this->language === null ) {
			$this->language = Language::factory( $this->options->getOption( ValueParser::OPT_LANG ) );
		}

		return $this->language;
	}

}
