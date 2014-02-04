<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use Language;
use Message;
use ValueParsers\CalenderModelParser;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\StringValueParser;
use ValueParsers\ValueParser;

/**
 * Class to parse values that can be formatted by MWTimeIsoFormatter
 * This includes parsing of localized values
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class MWTimeIsoParser extends StringValueParser {

	/**
	 * Prefix for precision message keys
	 */
	const MSG_PREFIX = 'wikibase-time-precision-';

	/**
	 * Placeholder value used when exploding parsable times
	 */
	private static $NUM_PLACEHOLDER = 990990990990990;

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
	 * @var Language
	 */
	protected $lang;

	/**
	 * @see StringValueParser::__construct
	 */
	public function __construct( ParserOptions $options = null ) {
		parent::__construct( $options );
		$this->lang = Language::factory( $this->getOptions()->getOption( ValueParser::OPT_LANG ) );
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
		$en = new Language();

		foreach( self::$precisionMsgKeys as $msgKeySuffix => $possiblePrecisions ) {
			$msg = new Message( self::MSG_PREFIX . $msgKeySuffix );
			//FIXME: Use the language passed in options!
			//The only reason we are not currently doing this is due to the formatting not currently Localizing
			//See the fix me in: MwTimeIsoFormatter::getMessage
			//$msg->inLanguage( $this->lang ); // todo check other translations?
			$msg->inLanguage( $en );
			$msg->numParams( array( self::$NUM_PLACEHOLDER ) );
			$string = $msg->text();

			list( $start, $end ) = explode( self::$NUM_PLACEHOLDER, $string, 2 );

			if( preg_match( '/^\s*' . preg_quote( $start ) . '(.+?)' . preg_quote( $end ) . '\s*$/i', $value, $matches ) ) {
				list( , $number ) = $matches;
				$number = $this->lang->parseFormattedNumber( $number );
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
			}
		}
		return false;
	}

	/**
	 * @param string $year
	 * @return TimeValue
	 */
	private function getTimeFromYear( $year ) {
		$timeParser = new \ValueParsers\TimeParser( new CalenderModelParser(), $this->getOptions() );
		return $timeParser->parse( '+' . $year . '-00-00T00:00:00Z' );
	}

}