<?php

namespace Wikibase\Repo\Parsers;

use Language;
use ValueParsers\CalendarModelParser;
use ValueParsers\DispatchingValueParser;
use ValueParsers\EraParser;
use ValueParsers\IsoTimestampParser;
use ValueParsers\MonthNameProvider;
use ValueParsers\MonthNameUnlocalizer;
use ValueParsers\ParserOptions;
use ValueParsers\PhpDateTimeParser;
use ValueParsers\ValueParser;
use ValueParsers\YearMonthDayTimeParser;
use ValueParsers\YearMonthTimeParser;
use ValueParsers\YearTimeParser;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Thiemo Mättig
 */
class TimeParserFactory {

	/**
	 * Default, canonical language code. In the MediaWiki world this is always English.
	 */
	const CANONICAL_LANGUAGE_CODE = 'en';

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

		$this->options->defaultOption( ValueParser::OPT_LANG, self::CANONICAL_LANGUAGE_CODE );
		$this->options->defaultOption(
			YearTimeParser::OPT_DIGIT_GROUP_SEPARATOR,
			$this->getDigitGroupSeparator()
		);
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
		$parsers[] = new YearMonthTimeParser( $this->monthNameProvider, $this->options );
		$parsers[] = $isoTimestampParser;
		$parsers[] = new MwTimeIsoParser( $this->options );
		$parsers[] = new YearMonthDayTimeParser( $eraParser, $this->options );
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

		if ( $languageCode === self::CANONICAL_LANGUAGE_CODE ) {
			$replacements = array();
		} else {
			$canonicalMonthNames = $this->monthNameProvider->getLocalizedMonthNames(
				self::CANONICAL_LANGUAGE_CODE
			);
			$replacements = $this->monthNameProvider->getMonthNumbers( $languageCode );

			foreach ( $replacements as $localizedMonthName => &$i ) {
				if ( !is_string( $localizedMonthName ) ) {
					unset( $replacements[$localizedMonthName] );
				} else {
					$i = $canonicalMonthNames[$i];
				}
			}
		}

		return new MonthNameUnlocalizer( $replacements );
	}

	/**
	 * @return string
	 */
	private function getDigitGroupSeparator() {
		$languageCode = $this->options->getOption( ValueParser::OPT_LANG );
		$language = Language::factory( $languageCode );
		$separatorMap = $language->separatorTransformTable();
		$canonical = YearTimeParser::CANONICAL_DIGIT_GROUP_SEPARATOR;

		return isset( $separatorMap[$canonical] ) ? $separatorMap[$canonical] : $canonical;
	}

}
