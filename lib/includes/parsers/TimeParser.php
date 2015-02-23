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

	const FORMAT_NAME = 'time';

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

		throw new ParseException( 'The format of the time could not be determined.', $value, self::FORMAT_NAME );
	}

	/**
	 * @return  StringValueParser[]
	 */
	private function getParsers() {
		$parsers = array();

		$eraParser = new EraParser( $this->getOptions() );
		$calenderModelParser = new CalendarModelParser( $this->getOptions() );

		// Year-month parser must be first to not parse "May 2014" as "2014-05-01".
		$parsers[] = new YearMonthTimeParser( $this->getOptions() );
		$parsers[] = new \ValueParsers\TimeParser(
			$calenderModelParser,
			$this->getOptions()
		);
		$parsers[] = new MWTimeIsoParser( $this->getOptions() );
		$parsers[] = new PhpDateTimeParser(
			$eraParser,
			$this->getOptions()
		);
		// Year parser must be last because it accepts some separator characters.
		$parsers[] = new YearTimeParser(
			$eraParser,
			$this->getOptions()
		);

		return $parsers;
	}

}
