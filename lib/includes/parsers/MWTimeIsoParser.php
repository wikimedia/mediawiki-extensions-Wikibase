<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use Message;
use ValueFormatters\TimeFormatter;
use ValueParsers\CalenderModelParser;
use ValueParsers\ParseException;
use ValueParsers\StringValueParser;
use ValueParsers\TimeParser;

class MWTimeIsoParser extends StringValueParser {

	const MSG_PREFIX = 'wikibase-time-precision-';
	const NUM_PLACEHOLDER = 990990990990990;

	/**
	 * @var array message key suffixes pointing to possible precisions
	 * Should be prefixed by MSG_PREFIX to get the message
	 */
	private static $precisionMsgKeys = array(
		'Gannum' => array( TimeValue::PRECISION_Ga ),
		'Mannum' => array( TimeValue::PRECISION_100Ma, TimeValue::PRECISION_10Ma, TimeValue::PRECISION_Ma ),
		'annum' => array( TimeValue::PRECISION_100ka, TimeValue::PRECISION_10ka ),
		'millennium' => array( TimeValue::PRECISION_ka ),
		'century' => array( TimeValue::PRECISION_100a ),
		'10annum' => array( TimeValue::PRECISION_10a ),
	);

	/**
	 * Parses the provided string and returns the result.
	 *
	 * @param string $value
	 *
	 * @throws ParseException
	 * @return TimeValue
	 */
	protected function stringParse( $value ) {
		$reconverted = $this->reconvertOutputString( $value );
		if( $reconverted !== false ) {
			return $reconverted;
		}

		throw new ParseException( 'foo' );
	}

	/**
	 * Analyzes a string if it is a time value that has been specified in one of the output
	 * precision formats specified in the settings. If so, this method re-converts such an output
	 * string to an object that can be used to instantiate a time.Time object.
	 *
	 * @param string $value
	 * @return TimeValue|bool
	 */
	private function reconvertOutputString( $value ) {
		global $wgLang;
		foreach( self::$precisionMsgKeys as $msgKeySuffix => $possiblePrecisions ) {
			$msg = new Message( self::MSG_PREFIX . $msgKeySuffix );
			$msg->inLanguage( $wgLang ); // todo check other translations?
			$msg->numParams( array( self::NUM_PLACEHOLDER ) );
			$string = $msg->text();

			list( $start, $end ) = explode( self::NUM_PLACEHOLDER, $string, 2 );

			if( preg_match( '/^\s*' . preg_quote( $start ) . '(.+?)' . preg_quote( $end ) . '\s*$/i', $value, $matches ) ) {
				list( , $number ) = $matches;
				$number = $wgLang->parseFormattedNumber( $number );
				$years = null;
				switch( $msgKeySuffix ) {
					case 'Gannum':
						return $this->getTimeFromYear( $number . str_repeat( '0', 9 ) );
					case 'Mannum':
						return $this->getTimeFromYear( $number . str_repeat( '0', 6 ) );
					case 'millennium':
						return $this->getTimeFromYear( $number . str_repeat( '0', 3 ) );
					case 'century':
						return $this->getTimeFromYear( $number . str_repeat( '0', 2 ) );
					case 'annum':
					case '10annum':
					return $this->getTimeFromYear( $number );
						break;
				}
				//todo
			}
		}
		return false;
	}

	/**
	 * @param string $year
	 * @return TimeValue
	 */
	private function getTimeFromYear( $year ) {
		$timeParser = new TimeParser( new CalenderModelParser(), $this->getOptions() );
		return $timeParser->parse( '+' . $year . '-00-00T00:00:00Z' );
	}

	/**
	 * @param string $year
	 * @return int precision
	 */
	private function getPrecisionFromYear( $year ) {
		$rightZeros = strlen( $year ) - strlen( rtrim( $year, '0' ) );
		$precision = TimeValue::PRECISION_YEAR - $rightZeros;
		if( $precision < TimeValue::PRECISION_Ga ) {
			$precision = TimeValue::PRECISION_Ga;
		}
		return $precision;
	}

}