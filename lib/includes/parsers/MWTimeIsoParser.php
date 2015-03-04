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
use ValueParsers\TimeParser as IsoTimestampParser;
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

	const FORMAT_NAME = 'mw-time-iso';

	/**
	 * @var array message keys showing the number of 0s that need to be appended to years when
	 *      parsed with the given message keys
	 */
	private static $precisionMsgKeys = array(
		TimeValue::PRECISION_YEAR1G => array(
			'wikibase-time-precision-Gannum',
			'wikibase-time-precision-BCE-Gannum',
		),
		TimeValue::PRECISION_YEAR1M => array(
			'wikibase-time-precision-Mannum',
			'wikibase-time-precision-BCE-Mannum',
		),
		TimeValue::PRECISION_YEAR1K => array(
			'wikibase-time-precision-millennium',
			'wikibase-time-precision-BCE-millennium',
		),
		TimeValue::PRECISION_YEAR100 => array(
			'wikibase-time-precision-century',
			'wikibase-time-precision-BCE-century',
		),
		TimeValue::PRECISION_YEAR10 => array(
			'wikibase-time-precision-annum',
			'wikibase-time-precision-BCE-annum',
			'wikibase-time-precision-10annum',
			'wikibase-time-precision-BCE-10annum',
		),
	);

	private static $paddedZeros = array(
		TimeValue::PRECISION_YEAR1G => 9,
		TimeValue::PRECISION_YEAR1M => 6,
		TimeValue::PRECISION_YEAR1K => 3,
		TimeValue::PRECISION_YEAR100 => 2,
		TimeValue::PRECISION_YEAR10 => 0
	);

	/**
	 * @var Language
	 */
	private $lang;

	/**
	 * @var ValueParser
	 */
	private $isoTimestampParser;

	/**
	 * @see StringValueParser::__construct
	 */
	public function __construct( ParserOptions $options = null ) {
		parent::__construct( $options );

		$this->lang = Language::factory( $this->getOption( ValueParser::OPT_LANG ) );
		$this->isoTimestampParser = new IsoTimestampParser(
			new CalendarModelParser( $this->options ),
			$this->options
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

		throw new ParseException( 'Failed to parse', $value, self::FORMAT_NAME );
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
		foreach( self::$precisionMsgKeys as $precision => $msgKeysGroup ) {
			foreach( $msgKeysGroup as $msgKey ) {
				$msg = new Message( $msgKey );
				//FIXME: Use the language passed in options!
				//The only reason we are not currently doing this is due to the formatting not currently Localizing
				//See the fix me in: MwTimeIsoFormatter::getMessage
				//$msg->inLanguage( $this->lang ); // todo check other translations?
				$msg->inLanguage( 'en' );
				$msgText = $msg->text();
				$isBceMsg = $this->isBceMsg( $msgKey );

				list( $start, $end ) = explode( '$1' , $msgText , 2 );
				if( preg_match( '/^\s*' . preg_quote( $start ) . '(.+?)' . preg_quote( $end ) . '\s*$/i', $value, $matches ) ) {
					list( , $number ) = $matches;
					return $this->parseNumber( $number, $precision, $isBceMsg );
				}

				// If the msg string ends with BCE also check for BC
				if( substr_compare( $end, 'BCE', - 3, 3 ) === 0 ) {
					if( preg_match( '/^\s*' . preg_quote( $start ) . '(.+?)' . preg_quote( substr( $end, 0, -1 ) ) . '\s*$/i', $value, $matches ) ) {
						list( , $number ) = $matches;
						return $this->parseNumber( $number, $precision, $isBceMsg );
					}

				}
			}

		}
		return false;
	}

	/**
	 * @param string $number
	 * @param int $precision
	 * @param boolean $isBceMsg
	 *
	 * @return TimeValue
	 */
	private function parseNumber( $number, $precision, $isBceMsg ) {
		$number = $this->lang->parseFormattedNumber( $number );
		$year = $number . str_repeat( '0', self::$paddedZeros[$precision] );

		$this->setPrecision( $precision );

		return $this->getTimeFromYear( $year, $isBceMsg );
	}

	/**
	 * @param string $msgKey
	 *
	 * @return boolean
	 */
	private function isBceMsg( $msgKey ) {
		return strstr( $msgKey, '-BCE-' );
	}

	/**
	 * @param string $year
	 * @param bool $isBce
	 *
	 * @return TimeValue
	 */
	private function getTimeFromYear( $year, $isBce ) {
		if( $isBce ) {
			$sign = EraParser::BEFORE_CURRENT_ERA;
		} else {
			$sign = EraParser::CURRENT_ERA;
		}

		$timeString = $sign . $year . '-00-00T00:00:00Z';

		return $this->isoTimestampParser->parse( $timeString );
	}

	/**
	 * @param int $precision
	 */
	private function setPrecision( $precision ) {
		$this->isoTimestampParser->getOptions()->setOption(
			IsoTimestampParser::OPT_PRECISION,
			$precision
		);
	}

}
