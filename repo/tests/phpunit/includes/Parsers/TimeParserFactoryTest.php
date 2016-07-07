<?php

namespace Wikibase\Repo\Tests\Parsers;

use DataValues\TimeValue;
use PHPUnit_Framework_TestCase;
use ValueParsers\IsoTimestampParser;
use ValueParsers\MonthNameProvider;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Repo\Parsers\TimeParserFactory;

/**
 * @covers Wikibase\Repo\Parsers\TimeParserFactory
 *
 * @group ValueParsers
 * @group WikibaseRepo
 * @group Wikibase
 * @group TimeParsers
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Thiemo Mättig
 */
class TimeParserFactoryTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return MonthNameProvider
	 */
	private function newMonthNameProvider() {
		$monthNameProvider = $this->getMock( MonthNameProvider::class );

		$monthNameProvider->expects( $this->any() )
			->method( 'getLocalizedMonthNames' )
			->will( $this->returnCallback( function( $languageCode ) {
				$monthNames = array();
				for ( $i = 1; $i <= 12; $i++ ) {
					$monthNames[$i] = $languageCode . 'Month' . $i;
				}
				return $monthNames;
			} ) );

		$monthNameProvider->expects( $this->any() )
			->method( 'getMonthNumbers' )
			->will( $this->returnCallback( function( $languageCode ) {
				$numbers = array();
				for ( $i = 1; $i <= 12; $i++ ) {
					$numbers[$languageCode . 'Month' . $i] = $i;
					$numbers[$languageCode . 'Month' . $i . 'Gen'] = $i;
				}
				return $numbers;
			} ) );

		return $monthNameProvider;
	}

	private function newTimeParserFactory(
		$languageCode,
		MonthNameProvider $monthNameProvider = null
	) {
		$options = new ParserOptions();
		$options->setOption( ValueParser::OPT_LANG, $languageCode );

		return new TimeParserFactory(
			$options,
			$monthNameProvider ?: $this->newMonthNameProvider()
		);
	}

	public function testGetTimeParser() {
		$factory = new TimeParserFactory( null, $this->newMonthNameProvider() );
		$parser = $factory->getTimeParser();

		$this->assertInstanceOf( ValueParser::class, $parser );
	}

	/**
	 * @dataProvider validInputProvider
	 */
	public function testParse( $value, TimeValue $expected, $languageCode ) {
		$factory = $this->newTimeParserFactory( $languageCode );
		$parser = $factory->getTimeParser();
		$actual = $parser->parse( $value );

		$this->assertSame( $expected->getArrayValue(), $actual->getArrayValue() );
	}

	public function validInputProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';
		$julian = 'http://www.wikidata.org/entity/Q1985786';

		$valid = array(
			/**
			 * @see Wikibase\Repo\Parsers\YearTimeParser
			 * @see Wikibase\Repo\Tests\Parsers\YearTimeParserTest
			 */
			'1999' =>
				array( '+1999-00-00T00:00:00Z', TimeValue::PRECISION_YEAR ),
			'2000' =>
				array( '+2000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR ),
			'2010' =>
				array( '+2010-00-00T00:00:00Z', TimeValue::PRECISION_YEAR ),
			'1980 ' =>
				array( '+1980-00-00T00:00:00Z', TimeValue::PRECISION_YEAR ),
			'1' =>
				array( '+0001-00-00T00:00:00Z', TimeValue::PRECISION_YEAR, $julian ),
			'-1000000001' =>
				array( '-1000000001-00-00T00:00:00Z', TimeValue::PRECISION_YEAR, $julian ),
			'+1000000001' =>
				array( '+1000000001-00-00T00:00:00Z', TimeValue::PRECISION_YEAR ),
			'1BC' =>
				array( '-0001-00-00T00:00:00Z', TimeValue::PRECISION_YEAR, $julian ),
			'1CE' =>
				array( '+0001-00-00T00:00:00Z', TimeValue::PRECISION_YEAR, $julian ),
			'1 1999 BC' =>
				array( '-11999-00-00T00:00:00Z', TimeValue::PRECISION_YEAR, $julian ),
			'1,000,000 BC' =>
				array( '-1000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M, $julian ),

			/**
			 * @see Wikibase\Repo\Parsers\YearMonthTimeParser
			 * @see Wikibase\Repo\Tests\Parsers\YearMonthTimeParserTest
			 */
			'1 1999' =>
				array( '+1999-01-00T00:00:00Z', TimeValue::PRECISION_MONTH ),
			'enMonth3 1999' =>
				array( '+1999-03-00T00:00:00Z', TimeValue::PRECISION_MONTH ),
			'1999 enMonth3' =>
				array( '+1999-03-00T00:00:00Z', TimeValue::PRECISION_MONTH ),

			/**
			 * @see ValueParsers\IsoTimestampParser
			 * @see ValueParsers\Test\IsoTimestampParserTest
			 */
			'+0000000000000000-01-01T00:00:00Z (Gregorian)' =>
				array( '+0000-01-01T00:00:00Z' ),
			'+0-01-20T00:00:00Z' =>
				array( '+0000-01-20T00:00:00Z', TimeValue::PRECISION_DAY, $julian ),
			'-10100-02-29' =>
				array( '-10100-02-29T00:00:00Z', TimeValue::PRECISION_DAY, $julian ),
			'+2015-01-00T00:00:00Z' =>
				array( '+2015-01-00T00:00:00Z', TimeValue::PRECISION_MONTH ),
			'+2015-00-00T00:00:00Z' =>
				array( '+2015-00-00T00:00:00Z', TimeValue::PRECISION_YEAR ),
			'2015-01-00' =>
				array( '+2015-01-00T00:00:00Z', TimeValue::PRECISION_MONTH ),
			'2015-00-00' =>
				array( '+2015-00-00T00:00:00Z', TimeValue::PRECISION_YEAR ),

			/**
			 * @see Wikibase\Repo\Parsers\MwTimeIsoParser
			 * @see Wikibase\Repo\Tests\Parsers\MwTimeIsoParserTest
			 */
			'13 billion years CE' =>
				array( '+13000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G ),
			'13,000 million years CE' =>
				array( '+13000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G ),
			'13,000 million years BCE' =>
				array( '-13000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G, $julian ),
			'1980s' =>
				array( '+1980-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10 ),

			/**
			 * @see ValueParsers\YearMonthDayTimeParser
			 * @see ValueParsers\Test\YearMonthDayTimeParserTest
			 * @see https://phabricator.wikimedia.org/T87019
			 * @see https://phabricator.wikimedia.org/T98194
			 * @see https://phabricator.wikimedia.org/T104862
			 */
			'29.02.1500' =>
				array( '+1500-02-29T00:00:00Z', TimeValue::PRECISION_DAY, $julian ),
			'02/29/1500' =>
				array( '+1500-02-29T00:00:00Z', TimeValue::PRECISION_DAY, $julian ),
			'1991 1 1' =>
				array( '+1991-01-01T00:00:00Z' ),
			'1991 1 20' =>
				array( '+1991-01-20T00:00:00Z' ),
			'2001 1 1' =>
				array( '+2001-01-01T00:00:00Z' ),
			'31 07 2009' =>
				array( '+2009-07-31T00:00:00Z' ),
			'31/07/2009' =>
				array( '+2009-07-31T00:00:00Z' ),

			/**
			 * @see ValueParsers\PhpDateTimeParser
			 * @see ValueParsers\Test\PhpDateTimeParserTest
			 */
			'10/10/10' =>
				array( '+0010-10-10T00:00:00Z', TimeValue::PRECISION_DAY, $julian ),
			'1 July 2013' =>
				array( '+2013-07-01T00:00:00Z' ),
			'1 Jul 2013' =>
				array( '+2013-07-01T00:00:00Z' ),
			'1 Jul 2013 BC' =>
				array( '-2013-07-01T00:00:00Z', TimeValue::PRECISION_DAY, $julian ),
			'1 Jul 2013CE' =>
				array( '+2013-07-01T00:00:00Z' ),
			'+1 Jul 2013' =>
				array( '+2013-07-01T00:00:00Z' ),
			'-1 Jul 2013' =>
				array( '-2013-07-01T00:00:00Z', TimeValue::PRECISION_DAY, $julian ),
			'-1.11.111' =>
				array( '-0111-11-01T00:00:00Z', TimeValue::PRECISION_DAY, $julian ),
			'1.11.111 BC' =>
				array( '-0111-11-01T00:00:00Z', TimeValue::PRECISION_DAY, $julian ),
			'1,11,111 BC' =>
				array( '-0111-11-01T00:00:00Z', TimeValue::PRECISION_DAY, $julian ),
			'1 11 111 BC' =>
				array( '-0111-11-01T00:00:00Z', TimeValue::PRECISION_DAY, $julian ),

			/**
			 * @see Wikibase\Lib\Tests\MwTimeIsoFormatterTest
			 */
			'16 laMonth8 2013' =>
				array( '+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY, $gregorian, 'la' ),
			'16 kaaMonth8, 2013' =>
				array( '+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY, $gregorian, 'kaa' ),
			'16 ptMonth8 2013' =>
				array( '+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY, $gregorian, 'pt' ),
			'16 yueMonth8 2013' =>
				array( '+2013-08-16T00:00:00Z', TimeValue::PRECISION_DAY, $gregorian, 'yue' ),
		);

		$argLists = array();

		foreach ( $valid as $value => $expected ) {
			$timestamp = $expected[0];
			$precision = isset( $expected[1] ) ? $expected[1] : TimeValue::PRECISION_DAY;
			$calendarModel = isset( $expected[2] ) ? $expected[2] : $gregorian;
			$languageCode = isset( $expected[3] ) ? $expected[3] : 'en';

			$argLists[] = array(
				(string)$value,
				new TimeValue( $timestamp, 0, 0, 0, $precision, $calendarModel ),
				$languageCode
			);
		}

		return $argLists;
	}

	/**
	 * @dataProvider invalidInputProvider
	 * @expectedException \ValueParsers\ParseException
	 */
	public function testParseThrowsException( $value ) {
		$factory = new TimeParserFactory( null, $this->newMonthNameProvider() );
		$parser = $factory->getTimeParser();

		$parser->parse( $value );
	}

	public function invalidInputProvider() {
		return array(
			// Stuff that's not even a string
			array( true ),
			array( false ),
			array( null ),
			array( 4.2 ),
			array( array() ),
			array( 42 ),

			// Strings that should not be recognozed as date time values
			array( 'June June June' ),
			array( '111 111 111' ),
			array( 'Jann 2014' ),
			array( '1980x' ),
			array( '1980ss' ),
			array( '1980UTC' ),
			array( '1980 America/New_York' ),
			array( '1980America/New_York' ),
			// Formats DYM and MYD do not exist.
			array( '20 1991 1' ),
			array( '1 1991 20' ),
			// No date parser should ever accept a day with no month.
			array( '2015-00-01' ),
			array( '+0-00-01T00:00:00Z' ),
			// No date parser should ever magically turn HMS times into dates.
			array( '12:31:59' ),
			array( '23:12:31' ),
			array( '23:12:59' ),
		);
	}

	/**
	 * @dataProvider parserOptionsProvider
	 */
	public function testParserOptions( $value, array $options, TimeValue $expected ) {
		$factory = new TimeParserFactory(
			new ParserOptions( $options ),
			$this->newMonthNameProvider()
		);
		$actual = $factory->getTimeParser()->parse( $value );

		$this->assertSame( $expected->getArrayValue(), $actual->getArrayValue() );
	}

	public function parserOptionsProvider() {
		$decadeOption = array( IsoTimestampParser::OPT_PRECISION => TimeValue::PRECISION_YEAR10 );
		$julianOption = array( IsoTimestampParser::OPT_CALENDAR => TimeValue::CALENDAR_JULIAN );

		$valid = array(
			// Precision option
			'2001 1' => array(
				$decadeOption,
				'+2001-01-00T00:00:00Z', TimeValue::PRECISION_YEAR10
			),
			'+2002-01-01T00:00:00Z' => array(
				$decadeOption,
				'+2002-01-01T00:00:00Z', TimeValue::PRECISION_YEAR10
			),
			'1 January 2003' => array(
				$decadeOption,
				'+2003-01-01T00:00:00Z', TimeValue::PRECISION_YEAR10
			),
			'2004 1 1' => array(
				$decadeOption,
				'+2004-01-01T00:00:00Z', TimeValue::PRECISION_YEAR10
			),
			'1 Jan 2005' => array(
				$decadeOption,
				'+2005-01-01T00:00:00Z', TimeValue::PRECISION_YEAR10
			),
			'2006' => array(
				$decadeOption,
				'+2006-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10
			),

			// Calendar option
			'2011 1' => array(
				$julianOption,
				'+2011-01-00T00:00:00Z', TimeValue::PRECISION_MONTH, TimeValue::CALENDAR_JULIAN
			),
			'+2012-01-01T00:00:00Z' => array(
				$julianOption,
				'+2012-01-01T00:00:00Z', TimeValue::PRECISION_DAY, TimeValue::CALENDAR_JULIAN
			),
			'1 January 2013' => array(
				$julianOption,
				'+2013-01-01T00:00:00Z', TimeValue::PRECISION_DAY, TimeValue::CALENDAR_JULIAN
			),
			'2014 1 1' => array(
				$julianOption,
				'+2014-01-01T00:00:00Z', TimeValue::PRECISION_DAY, TimeValue::CALENDAR_JULIAN
			),
			'1 Jan 2015' => array(
				$julianOption,
				'+2015-01-01T00:00:00Z', TimeValue::PRECISION_DAY, TimeValue::CALENDAR_JULIAN
			),
			'2016' => array(
				$julianOption,
				'+2016-00-00T00:00:00Z', TimeValue::PRECISION_YEAR, TimeValue::CALENDAR_JULIAN
			),
		);

		$cases = array();

		foreach ( $valid as $value => $args ) {
			$options = $args[0];
			$timestamp = $args[1];
			$precision = $args[2];
			$calendarModel = isset( $args[3] ) ? $args[3] : TimeValue::CALENDAR_GREGORIAN;

			$cases[] = array(
				(string)$value,
				$options,
				new TimeValue( $timestamp, 0, 0, 0, $precision, $calendarModel )
			);
		}

		return $cases;
	}

	/**
	 * @dataProvider localizedMonthNameProvider
	 */
	public function testMonthNameUnlocalizer( $date, $languageCode, $expected ) {
		$factory = $this->newTimeParserFactory( $languageCode );
		$unlocalizer = $factory->getMonthNameUnlocalizer();

		$this->assertSame( $expected, $unlocalizer->unlocalize( $date ) );
	}

	public function localizedMonthNameProvider() {
		$testCases = array(
			// Nominative month names.
			array( '1 deMonth7 2013', 'de', '1 enMonth7 2013' ),
			array( '1 afMonth1 1999', 'af', '1 enMonth1 1999' ),
			array( '16 barMonth1 1999', 'bar', '16 enMonth1 1999' ),
			array( '12 de-atMonth1 2013', 'de-at', '12 enMonth1 2013' ),

			// Genitive month names.
			array( '1 deMonth7Gen 2013', 'de', '1 enMonth7 2013' ),
			array( '31 laMonth12Gen 2013', 'la', '31 enMonth12 2013' ),
			array( '1 afMonth1Gen 1999', 'af', '1 enMonth1 1999' ),
			array( '1 deMonth3Gen 1999', 'de', '1 enMonth3 1999' ),

			// Nothing to do in English.
			array( '1 enMonth6 2013', 'en', '1 enMonth6 2013' ),
			array( '1 enMonth1 2013', 'en', '1 enMonth1 2013' ),
			array( '1 enMonth1 1999', 'en', '1 enMonth1 1999' ),

			// No localized month name found.
			array( '16 FooBarBarxxx 1999', 'bar', '16 FooBarBarxxx 1999' ),
			array( '16 Martii 1999', 'de', '16 Martii 1999' ),
			array( 'Jann 2013', 'de', 'Jann 2013' ),
			array( '16 May 1999', 'de', '16 May 1999' ),
			array( '16 Dezember 1999', 'la', '16 Dezember 1999' ),

			// Replace the longest unlocalized substring first.
			array( 'deMonth7 deMonth12', 'de', 'deMonth7 enMonth12' ),
			array( 'deMonth12 deMonth7', 'de', 'enMonth12 deMonth7' ),
			array( 'deMonth7 enMonth12', 'de', 'enMonth7 enMonth12' ),
			array( 'enMonth7 deMonth12', 'de', 'enMonth7 enMonth12' ),
			array( 'deMonth12 deMonth7 deMonth8', 'de', 'enMonth12 deMonth7 deMonth8' ),

			// Do not mess with already unlocalized month names.
			array( 'enMonth1', 'de', 'enMonth1' ),
			array( 'enMonth4', 'la', 'enMonth4' ),
			array( 'enMonth12', 'de', 'enMonth12' ),
			array( '15 enMonth3 44 BC', 'nrm', '15 enMonth3 44 BC' ),
			array( 'deMonth6 enMonth6', 'de', 'deMonth6 enMonth6' ),
			array( 'enMonth7 deMonth7', 'de', 'enMonth7 deMonth7' ),

			// But shortening is ok even if a substring looks like it's already unlocalized.
			array( 'warMonth5Gen', 'war', 'enMonth5' ),
			array( 'enMonth7 deMonth7Gen', 'de', 'enMonth7 enMonth7' ),

			// Do not mess with strings that are clearly not a valid date.
			array( 'deMonth7 deMonth7', 'de', 'deMonth7 deMonth7' ),

			// Word boundaries currently do not prevent unlocalization on purpose.
			array( 'deMonth52013', 'de', 'enMonth52013' ),
			array( 'deMonth2ii', 'de', 'enMonth2ii' ),

			// Capitalization is currently significant. This may need to depend on the languages.
			array( '1 juli 2013', 'de', '1 juli 2013' ),
		);

		// Loop through some other languages
		$languageCodes = array( 'war', 'ceb', 'uk', 'ru', 'de' );

		foreach ( $languageCodes as $languageCode ) {
			for ( $i = 1; $i <= 12; $i++ ) {
				$expected = 'enMonth' . $i;

				$testCases[] = array( $languageCode . 'Month' . $i, $languageCode, $expected );
				$testCases[] = array( $languageCode . 'Month' . $i . 'Gen', $languageCode, $expected );
			}
		}

		return $testCases;
	}

	/**
	 * @dataProvider localizedMonthName_withLanguageChainProvider
	 */
	public function testMonthNameUnlocalizer_withLanguageChain( $date, array $languageCodes, $expected ) {
		foreach ( $languageCodes as $languageCode ) {
			$factory = $this->newTimeParserFactory( $languageCode );
			$unlocalizer = $factory->getMonthNameUnlocalizer();
			$date = $unlocalizer->unlocalize( $date );
		}

		$this->assertSame( $expected, $date );
	}

	public function localizedMonthName_withLanguageChainProvider() {
		return array(
			// First language contains the word.
			array( 'deMonth2', array( 'de', 'la' ), 'enMonth2' ),

			// Second language contains the word.
			array( 'enMonth2', array( 'de', 'en' ), 'enMonth2' ),
			array( 'deMonth2', array( 'en', 'de' ), 'enMonth2' ),
			array( 'laMonth2', array( 'de', 'la' ), 'enMonth2' ),
			array( 'msMonth6', array( 'de', 'ms' ), 'enMonth6' ),

			// No language contains the word.
			array( 'enMonth6', array( 'de', 'la' ), 'enMonth6' ),
		);
	}

	public function testMonthNameUnlocalizer_withUnlocalizedMonthNumbers() {
		$monthNameProvider = $this->getMock( MonthNameProvider::class );
		$monthNameProvider->expects( $this->any() )
			->method( 'getLocalizedMonthNames' )
			->will( $this->returnValue( [ 2 => 'Localized' ] ) );
		$monthNameProvider->expects( $this->any() )
			->method( 'getMonthNumbers' )
			->will( $this->returnValue( [ '2' => 2 ] ) );

		$factory = $this->newTimeParserFactory( 'ko', $monthNameProvider );
		$unlocalizer = $factory->getMonthNameUnlocalizer();
		$this->assertSame( '2000', $unlocalizer->unlocalize( '2000' ) );
	}

}
