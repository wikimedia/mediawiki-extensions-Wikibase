<?php

namespace Wikibase\Repo\Parsers;

use ValueParsers\CalendarModelParser;
use ValueParsers\DispatchingValueParser;
use ValueParsers\EraParser;
use ValueParsers\IsoTimestampParser;
use ValueParsers\MonthNameUnlocalizer;
use ValueParsers\ParserOptions;
use ValueParsers\PhpDateTimeParser;
use ValueParsers\ValueParser;
use ValueParsers\YearMonthDayTimeParser;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Thiemo Mättig
 *
 * @todo move me to DataValues-time
 */
class TimeParserFactory {

	/**
	 * @var ParserOptions
	 */
	private $options;

	/**
	 * @var MonthNameProvider
	 */
	private $monthNameProvider;

	/**
	 * @param ParserOptions|null $options
	 * @param MonthNameProvider|null $monthNameProvider
	 */
	public function __construct(
		ParserOptions $options = null,
		MonthNameProvider $monthNameProvider = null
	) {
		$this->options = $options ?: new ParserOptions();
		$this->monthNameProvider = $monthNameProvider ?: new MediaWikiMonthNameProvider();

		$this->options->defaultOption( ValueParser::OPT_LANG, 'en' );

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
		$parsers[] = new MwTimeIsoParser( $this->options );
		$parsers[] = new YearMonthDayTimeParser( $eraParser );
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
		$baseLanguageCode = 'en';

		$replacements = array();

		if ( $languageCode !== $baseLanguageCode ) {
			$replacements = $this->monthNameProvider->getMonthNameReplacements( $languageCode, $baseLanguageCode );
		}

		return new MonthNameUnlocalizer( $replacements );
	}

}
