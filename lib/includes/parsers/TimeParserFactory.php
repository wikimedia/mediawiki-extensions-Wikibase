<?php

namespace Wikibase\Lib\Parsers;

use Language;
use ValueParsers\CalendarModelParser;
use ValueParsers\DispatchingValueParser;
use ValueParsers\EraParser;
use ValueParsers\IsoTimestampParser;
use ValueParsers\MonthNameUnlocalizer;
use ValueParsers\ParserOptions;
use ValueParsers\PhpDateTimeParser;
use ValueParsers\ValueParser;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Thiemo Mättig
 */
class TimeParserFactory {

	const BASE_LANGUAGE_CODE = 'en';

	/**
	 * @var array[]
	 */
	private static $monthNameReplacements = array();

	/**
	 * @var ParserOptions
	 */
	private $options;

	/**
	 * @param ParserOptions|null $options
	 */
	public function __construct( ParserOptions $options = null ) {
		$this->options = $options ?: new ParserOptions();

		$this->options->defaultOption( ValueParser::OPT_LANG, self::BASE_LANGUAGE_CODE );
	}

	/**
	 * @return ValueParser
	 */
	public function getTimeParser() {
		return new DispatchingValueParser( $this->getTimeParsers(), 'time' );
	}

	/**
	 * @return ValueParser[]
	 */
	private function getTimeParsers() {
		$eraParser = new EraParser( $this->options );
		$isoTimestampParser = new IsoTimestampParser(
			new CalendarModelParser( $this->options ),
			$this->options
		);

		$parsers = array();

		// Year-month parser must be first, otherwise "May 2014" may be parsed as "2014-05-01".
		$parsers[] = new YearMonthTimeParser( $this->options );
		$parsers[] = $isoTimestampParser;
		$parsers[] = new MWTimeIsoParser( $this->options );
		$parsers[] = new PhpDateTimeParser(
			$this->getMonthNameUnlocalizer(),
			$eraParser,
			$isoTimestampParser
		);
		// Year parser must be last because it accepts some separator characters.
		$parsers[] = new YearTimeParser( $eraParser, $this->options );

		return $parsers;
	}

	/**
	 * @return MonthNameUnlocalizer
	 */
	public function getMonthNameUnlocalizer() {
		$languageCode = $this->options->getOption( ValueParser::OPT_LANG );

		if ( $languageCode === self::BASE_LANGUAGE_CODE ) {
			$replacements = array();
		} else {
			$replacements = $this->getMonthNameReplacements( $languageCode );
		}

		return new MonthNameUnlocalizer( $replacements );
	}

	/**
	 * @param string $languageCode
	 *
	 * @return string[]
	 */
	private function getMonthNameReplacements( $languageCode ) {
		// Caching these arrays usually needs 1 KB but reduces test run time by about 50 %.
		if ( !isset( self::$monthNameReplacements[$languageCode] ) ) {
			self::$monthNameReplacements[$languageCode] = $this->getMwMonthNameReplacements(
				$languageCode
			);
		}

		return self::$monthNameReplacements[$languageCode];
	}

	/**
	 * Creates replacements for the MonthNameUnlocalizer using information retrieved via MediaWiki's
	 * Language object. Takes full month names, genitive names and abbreviations into account.
	 *
	 * @param string $languageCode
	 *
	 * @return string[]
	 */
	private function getMwMonthNameReplacements( $languageCode ) {
		$language = Language::factory( $languageCode );
		$baseLanguage = Language::factory( self::BASE_LANGUAGE_CODE );

		$replacements = array();

		for ( $i = 1; $i <= 12; $i++ ) {
			$canonical = $baseLanguage->getMonthName( $i );

			$replacements[$language->getMonthName( $i )] = $canonical;
			$replacements[$language->getMonthNameGen( $i )] = $canonical;
			$replacements[$language->getMonthAbbreviation( $i )] = $canonical;
		}

		return $replacements;
	}

}
