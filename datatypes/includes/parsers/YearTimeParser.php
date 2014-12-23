<?php

namespace Wikibase\Lib\Parsers;

use DataValues\TimeValue;
use Language;
use ValueParsers\CalendarModelParser;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\StringValueParser;
use ValueParsers\ValueParser;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * @todo move me to DataValues-time
 */
class YearTimeParser extends StringValueParser {

	const FORMAT_NAME = 'year';

	/**
	 * @var EraParser
	 */
	private $eraParser;

	/**
	 * @var Language
	 */
	private $lang;

	/**
	 * @var \ValueParsers\TimeParser
	 */
	private $timeValueTimeParser;

	/**
	 * @param ValueParser $eraParser
	 * @param ParserOptions $options
	 */
	public function __construct( ValueParser $eraParser, ParserOptions $options = null ) {
		if( is_null( $options ) ) {
			$options = new ParserOptions();
		}
		parent::__construct( $options );
		$this->lang = Language::factory( $this->getOptions()->getOption( ValueParser::OPT_LANG ) );

		$this->timeValueTimeParser = new \ValueParsers\TimeParser(
			new CalendarModelParser(),
			$this->getOptions()
		);

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

		// Negative dates usually don't have a month, assume non-digits are thousands separators
		if( $sign === EraParser::BEFORE_CURRENT_ERA ) {
			$separatorMap = $this->lang->separatorTransformTable();

			if ( is_array( $separatorMap ) && array_key_exists( ',', $separatorMap ) ) {
				$separator = $separatorMap[','];
			} else {
				$separator = ',';
			}

			// Always accept ISO (e.g. "1 000 BC") as well as programming style (e.g. "-1_000")
			$year = preg_replace( '/(?<=\d)[' . preg_quote( $separator, '/' ) . '\s_](?=\d)/', '',
				$year );
		}

		if( !preg_match( '/^\d+$/', $year ) ) {
			throw new ParseException( 'Failed to parse year', $value, self::FORMAT_NAME );
		}

		return $this->timeValueTimeParser->parse( $sign . $year . '-00-00T00:00:00Z' );
	}

}
