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

	/**
	 * @var \ValueParsers\TimeParser
	 */
	private $timeValueTimeParser;

	/**
	 * @var EraParser
	 */
	private $eraParser;

	/**
	 * @param EraParser $eraParser
	 * @param ParserOptions $options
	 */
	public function __construct( EraParser $eraParser, ParserOptions $options = null ) {
		if( is_null( $options ) ) {
			$options = new ParserOptions();
		}
		parent::__construct( $options );

		$this->timeValueTimeParser = new \ValueParsers\TimeParser(
			new CalendarModelParser(),
			$this->getOptions()
		);

		//@todo inject me [=
		$this->eraParser = $eraParser;
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
		list( $sign, $year ) = $this->eraParser->parse( $value );

		if( !preg_match( '/\d+/', $year ) ) {
			throw new ParseException( 'Failed to parse year: ' . $value );
		}

		return $this->timeValueTimeParser->parse( $sign . $year . '-00-00T00:00:00Z' );
	}

}