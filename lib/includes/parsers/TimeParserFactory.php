<?php

namespace Wikibase\Lib\Parsers;

use Language;
use ValueParsers\CalendarModelParser;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Thiemo Mättig
 */
class TimeParserFactory {

	private $options;

	function __construct( ParserOptions $options = null ) {
		$this->options = $options ?: new ParserOptions();

		$this->options->defaultOption( ValueParser::OPT_LANG, 'en' );
	}

	/**
	 * @return ValueParser
	 */
	public function getTimeParser() {
		return new CompositeValueParser( $this->getTimeParsers(), 'time' );
	}

	/**
	 * @return ValueParser[]
	 */
	private function getTimeParsers() {
		$parsers = array();

		$eraParser = new EraParser( $this->options );
		$calendarModelParser = new CalendarModelParser( $this->options );
		$monthNameUnlocalizer = $this->getMonthNameUnlocalizer();
		$isoTimestampParser = new \ValueParsers\TimeParser(
			$calendarModelParser,
			$this->options
		);

		// Year-month parser must be first to not parse "May 2014" as "2014-05-01".
		$parsers[] = new YearMonthTimeParser( $this->options );
		$parsers[] = $isoTimestampParser;
		$parsers[] = new MWTimeIsoParser( $this->options );
		$parsers[] = new PhpDateTimeParser(
			$eraParser,
			$monthNameUnlocalizer,
			$isoTimestampParser
		);
		// Year parser must be last because it accepts some separator characters.
		$parsers[] = new YearTimeParser(
			$eraParser,
			$this->options
		);

		return $parsers;
	}

	/**
	 * Factory to create a MonthNameUnlocalizer using information retrieved via MediaWiki's Language
	 * object. Takes full month names, genitive names and abbreviations into account.
	 *
	 * @return MonthNameUnlocalizer
	 */
	private function getMonthNameUnlocalizer() {
		$replacements = array();

		$languageCode = $this->options->getOption( ValueParser::OPT_LANG );
		if ( $languageCode !== MonthNameUnlocalizer::BASE_LANGUAGE_CODE ) {
			$replacements = $this->getMonthNameReplacements( $languageCode );
		}

		return new MonthNameUnlocalizer( $replacements );
	}

	/**
	 * @param string $languageCode Language code of the source strings to be unlocalized.
	 *
	 * @return string[]
	 */
	private function getMonthNameReplacements( $languageCode ) {
		$replacements = array();

		$baseLanguage = Language::factory( MonthNameUnlocalizer::BASE_LANGUAGE_CODE );
		$language = Language::factory( $languageCode );

		for ( $i = 1; $i <= 12; $i++ ) {
			$canonical = $baseLanguage->getMonthName( $i );

			$replacements[$language->getMonthName( $i )] = $canonical;
			$replacements[$language->getMonthNameGen( $i )] = $canonical;
			$replacements[$language->getMonthAbbreviation( $i )] = $canonical;
		}

		return $replacements;
	}

}
