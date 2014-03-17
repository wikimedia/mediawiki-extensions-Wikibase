<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use ValueParsers\CalendarModelParser;
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

	public function __construct( ParserOptions $options = null ) {
		if( is_null( $options ) ) {
			$options = new ParserOptions();
		}
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

		$eraParser = new EraParser( $this->getOptions() );
		$calanderModelParser = new CalendarModelParser( $this->getOptions() );

		$parsers[] = new YearTimeParser(
			$eraParser,
			$this->getOptions()
		);
		$parsers[] = new YearMonthTimeParser( $this->getOptions() );
		$parsers[] = new \ValueParsers\TimeParser(
			$calanderModelParser,
			$this->getOptions()
		);
		$parsers[] = new MWTimeIsoParser( $this->getOptions() );
		$parsers[] = new DateTimeParser(
			$eraParser,
			$this->getOptions()
		);

		return $parsers;
	}

}