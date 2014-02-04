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

	/**
	 * @since 0.5
	 * @todo I need to be in the datavalues time parser really....?
	 */
	const OPT_CALENDER_GREGORIAN = 'gregorian';
	const OPT_CALENDER_JULIAN = 'julian';
	const OPT_PRECISION_NONE = 'noprecision';

	public function __construct( ParserOptions $options = null ) {
		if( is_null( $options ) ) {
			$options = new ParserOptions();
		}
		$options->defaultOption( TimeParser::OPT_CALENDER, TimeParser::OPT_CALENDER_GREGORIAN );
		$options->defaultOption( TimeParser::OPT_PRECISION, TimeParser::OPT_PRECISION_NONE );

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

		$parsers[] = new YearTimeParser( $this->getOptions() );
		$parsers[] = new YearMonthTimeParser( $this->getOptions() );
		$parsers[] = new \ValueParsers\TimeParser( new CalenderModelParser( $this->getOptions() ), $this->getOptions() );
		$parsers[] = new MWTimeIsoParser( $this->getOptions() );
		$parsers[] = new DateTimeParser( $this->getOptions() );

		return $parsers;
	}

}