<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use ValueParsers\CalendarModelParser;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\StringValueParser;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * @todo move me to DataValues-time
 */
class YearTimeParser extends StringValueParser {

	private $BCEregex = '(B\.?C(\.?E)?|Before\s(Christ|Common\sEra))';
	private $CEregex = '(C\.?E|A\.?D|Common\sEra|Before\sChrist|Anno\sDomini)';

	/**
	 * @var \ValueParsers\TimeParser
	 */
	protected $timeValueTimeParser;

	/**
	 * @param ParserOptions $options
	 */
	public function __construct( ParserOptions $options = null ) {
		if( is_null( $options ) ) {
			$options = new ParserOptions();
		}
		parent::__construct( $options );

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
		if( preg_match( '/^[+-]?(\d+)$/', $value, $matches ) ||
			preg_match( '/^(\d+)\s*(' . $this->CEregex . '|' .  $this->BCEregex . ')$/i', $value, $matches ) )
		{
			return $this->getTimeFromYear( $value );
		}
		throw new ParseException( 'Failed to parse year: ' . $value );
	}

	/**
	 * @param string $year
	 * @return TimeValue
	 */
	private function getTimeFromYear( $year ) {
		$sign = $this->getSignFromYear( $year );
		$year = $this->cleanYear( $year );
		return $this->timeValueTimeParser->parse( $sign . $year . '-00-00T00:00:00Z' );
	}

	/**
	 * @param string $year
	 * @return string the sign + or -
	 */
	private function getSignFromYear( $year ) {
		$char1 = substr( $year, 0, 1 );
		if( $char1 === '-' || $char1 === '+' ) {
			return $char1;
		}
		if( preg_match( '/^(\d+)\s*' . $this->BCEregex . '$/i', $year, $matches ) ) {
			return '-';
		}
		return '+';
	}

	/**
	 * @param string $year
	 *
	 * @return string
	 */
	private function cleanYear( $year ) {
		preg_match(
			'/^[\+\-]?(\d+)(\s*(' . $this->CEregex . '|' .  $this->BCEregex . '))?$/i',
			$year,
			$matches
		);
		return $matches[1];
	}

}