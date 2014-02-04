<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use Language;
use Message;
use RuntimeException;
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
 *
 * @todo move me to DataValues-time
 */
class MWTimeIsoParser extends StringValueParser {

	/**
	 * @var array message keys pointing to the number of 0s that need to be appended to the year
	 */
	private static $precisionMsgKeys = array(
		'wikibase-time-precision-Gannum' => 9,
		'wikibase-time-precision-Mannum' => 6,
		'wikibase-time-precision-millennium' => 3,
		'wikibase-time-precision-century' => 2,
		'wikibase-time-precision-annum' => 0,
		'wikibase-time-precision-10annum' => 0,
	);

	/**
	 * @var Language
	 */
	protected $lang;

	/**
	 * @var \ValueParsers\TimeParser
	 */
	protected $timeValueTimeParser;

	/**
	 * @see StringValueParser::__construct
	 */
	public function __construct( ParserOptions $options = null ) {
		parent::__construct( $options );
		$this->lang = Language::factory( $this->getOptions()->getOption( ValueParser::OPT_LANG ) );
		$this->timeValueTimeParser = new \ValueParsers\TimeParser( new CalenderModelParser(), $this->getOptions() );
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

		throw new ParseException( 'Failed to parse MwTimeIso' );
	}

	/**
	 * Analyzes a string if it is a time value that has been specified in one of the output
	 * precision formats specified in the settings. If so, this method re-converts such an output
	 * string to an object that can be used to instantiate a time.Time object.
	 *
	 * @param string $value
	 *
	 * @throws RuntimeException
	 * @return TimeValue|bool
	 */
	private function reconvertOutputString( $value ) {
		foreach( self::$precisionMsgKeys as $msgKey => $repeat0Char ) {
			$msg = new Message( $msgKey );
			//FIXME: Use the language passed in options!
			//The only reason we are not currently doing this is due to the formatting not currently Localizing
			//See the fix me in: MwTimeIsoFormatter::getMessage
			//$msg->inLanguage( $this->lang ); // todo check other translations?
			$msg->inLanguage( 'en' );

			list( $start, $end ) = explode( '$1' , $msg->text() , 2 );
			if( preg_match( '/^\s*' . preg_quote( $start ) . '(.+?)' . preg_quote( $end ) . '\s*$/i', $value, $matches ) ) {
				list( , $number ) = $matches;
				$number = $this->lang->parseFormattedNumber( $number );
				return $this->getTimeFromYear( $number . str_repeat( '0', $repeat0Char ) );
			}
		}
		return false;
	}

	/**
	 * @param string $year
	 * @return TimeValue
	 */
	private function getTimeFromYear( $year ) {
		return $this->timeValueTimeParser->parse( '+' . $year . '-00-00T00:00:00Z' );
	}

}