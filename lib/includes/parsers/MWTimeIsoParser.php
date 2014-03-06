<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use Language;
use Message;
use RuntimeException;
use ValueParsers\CalendarModelParser;
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
	 * @var array message keys showing the number of 0s that need to be appended to years when
	 *      parsed with the given message keys
	 */
	private static $precisionMsgKeys = array(
		9 => array(
			'wikibase-time-precision-Gannum',
			'wikibase-time-precision-BCE-Gannum',
		),
		6 => array(
			'wikibase-time-precision-Mannum',
			'wikibase-time-precision-BCE-Mannum',
		),
		3 => array(
			'wikibase-time-precision-millennium',
			'wikibase-time-precision-BCE-millennium',
		),
		2 => array(
			'wikibase-time-precision-century',
			'wikibase-time-precision-BCE-century',
		),
		0 => array(
			'wikibase-time-precision-annum',
			'wikibase-time-precision-BCE-annum',
			'wikibase-time-precision-10annum',
			'wikibase-time-precision-BCE-10annum',
		),
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
		if( is_null( $options ) ) {
			$options = new ParserOptions();
		}

		parent::__construct( $options );
		$this->lang = Language::factory( $this->getOptions()->getOption( ValueParser::OPT_LANG ) );

		$this->timeValueTimeParser = new \ValueParsers\TimeParser(
			new CalendarModelParser(),
			$this->getOptions()
		);
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
		foreach( self::$precisionMsgKeys as $repeat0Char => $msgKeys ) {
			foreach( $msgKeys as $msgKey ) {
				$msg = new Message( $msgKey );
				//FIXME: Use the language passed in options!
				//The only reason we are not currently doing this is due to the formatting not currently Localizing
				//See the fix me in: MwTimeIsoFormatter::getMessage
				//$msg->inLanguage( $this->lang ); // todo check other translations?
				$msg->inLanguage( 'en' );
				$msgText = $msg->text();
				$isBceMsg = strstr( $msgKey, '-BCE-' );

				list( $start, $end ) = explode( '$1' , $msgText , 2 );
				if( preg_match( '/^\s*' . preg_quote( $start ) . '(.+?)' . preg_quote( $end ) . '\s*$/i', $value, $matches ) ) {
					list( , $number ) = $matches;
					$number = $this->lang->parseFormattedNumber( $number );

					return $this->getTimeFromYear(
						$number . str_repeat( '0', $repeat0Char ),
						$isBceMsg
					);
				}
				// If the msg string ends with BCE also check for BC
				if( substr_compare( $end, 'BCE', - 3, 3 ) === 0 ) {
					if( preg_match( '/^\s*' . preg_quote( $start ) . '(.+?)' . preg_quote( substr( $end, 0, -1 ) ) . '\s*$/i', $value, $matches ) ) {
						list( , $number ) = $matches;
						$number = $this->lang->parseFormattedNumber( $number );

						return $this->getTimeFromYear(
							$number . str_repeat( '0', $repeat0Char ),
							$isBceMsg
						);
					}

				}
			}

		}
		return false;
	}

	/**
	 * @param string $year
	 * @param bool $isBce
	 *
	 * @return TimeValue
	 */
	private function getTimeFromYear( $year, $isBce ) {
		if( $isBce ) {
			$sign = '-';
		} else {
			$sign = '+';
		}
		$timeString = $sign . $year . '-00-00T00:00:00Z';
		return $this->timeValueTimeParser->parse( $timeString );
	}

}