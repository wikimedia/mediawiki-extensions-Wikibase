<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use ValueParsers\CalenderModelParser;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\StringValueParser;

/**
 * Time Parser
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * @todo move me to DataValues-time
 */
class TimeParser extends StringValueParser {

	/**
	 * @since 0.5
	 * @todo I need to be in the datavalues time parser really....?
	 */
	const OPT_PRECISION = 'precision';
	const OPT_CALENDER = 'calender';

	public function __construct( ParserOptions $options = null ) {

		$options->defaultOption( self::OPT_PRECISION, null );
		$options->defaultOption( self::OPT_CALENDER, null );

		parent::__construct( $options );
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
		foreach ( $this->getParsers() as $parser ) {
			try {
				return $parser->parse( $value );
			}
			catch ( ParseException $parseException ) {
				continue;
			}
		}

		throw new ParseException( 'The format of the time could not be determined. Parsing failed.' );
	}

	/**
	 * @return  StringValueParser[]
	 */
	protected function getParsers() {
		$parsers = array();

		$parsers[] = new YearTimeParser( $this->options );
		$parsers[] = new YearMonthTimeParser( $this->options );
		$parsers[] = new \ValueParsers\TimeParser( new CalenderModelParser( $this->options ), $this->options );
		$parsers[] = new MWTimeIsoParser( $this->options );
		$parsers[] = new DateTimeParser( $this->options );

		return $parsers;
	}

}