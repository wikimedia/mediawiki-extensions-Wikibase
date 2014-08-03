<?php

namespace Wikibase\Lib\Parsers\Test;

use Language;
use ValueParsers\ParserOptions;
use Wikibase\Lib\Parsers\MonthNameUnlocalizer;

/**
 * @covers Wikibase\Lib\Parsers\MonthNameUnlocalizer
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Thiemo Mättig
 */
class MonthNameUnlocalizerTest extends \PHPUnit_Framework_TestCase {

	public function localizedDateProvider() {
		$testCases = array(
			// Nominative month names.
			array( '1 Juli 2013', 'de', '1 July 2013' ),
			array( '1 Januarie 1999', 'af', '1 January 1999' ),
			array( '16 Jenna 1999', 'bar', '16 January 1999' ),
			array( '12 Jänner 2013', 'de-at', '12 January 2013' ),

			// Genitive month names.
			array( '1 Julis 2013', 'de', '1 July 2013' ),
			array( '31 Decembris 2013', 'la', '31 December 2013' ),

			// Abbreviations.
			array( '1 Jan 1999', 'af', '1 January 1999' ),
			array( '1 Mär. 1999', 'de', '1 March 1999' ),

			// Nothing to do in English.
			array( '1 June 2013', 'en', '1 June 2013' ),
			array( '1 Jan 2013', 'en', '1 Jan 2013' ),
			array( '1 January 1999', 'en', '1 January 1999' ),

			// No localized month name found.
			array( '16 FooBarBarxxx 1999', 'bar', '16 FooBarBarxxx 1999' ),
			array( '16 Martii 1999', 'de', '16 Martii 1999' ),
			array( 'Jann 2013', 'de', 'Jann 2013' ),
			array( '16 May 1999', 'de', '16 May 1999' ),
			array( '16 Dezember 1999', 'la', '16 Dezember 1999' ),

			// Replace the longest unlocalized substring first.
			array( 'Juli Januar', 'de', 'Juli January' ),
			array( 'Juli Mai', 'de', 'July Mai' ),
			array( 'Juli December', 'de', 'July December' ),
			array( 'July Dezember', 'de', 'July December' ),
			array( 'Januar Mär Dez', 'de', 'January Mär Dez' ),

			// Do not mess with already unlocalized month names.
			array( 'January', 'de', 'January' ),
			array( 'April', 'la', 'April' ),
			array( 'Dec', 'de', 'Dec' ),
			array( '15 March 44 BC', 'nrm', '15 March 44 BC' ),
			array( 'Juni June', 'de', 'Juni June' ),
			array( 'July Jul', 'de', 'July Jul' ),

			// But shortening is ok even if a substring looks like it's already unlocalized.
			array( 'Mayo', 'war', 'May' ),
			array( 'July Julis', 'de', 'July July' ),

			// Do not mess with strings that are clearly not a valid date.
			array( 'Juli Juli', 'de', 'Juli Juli' ),

			// Word boundaries currently do not prevent unlocalization on purpose.
			array( 'Mai2013', 'de', 'May2013' ),
			array( 'Februarii', 'de', 'Februaryii' ),

			// Capitalization is currently significant. This may need to depend on the languages.
			array( '1 juli 2013', 'de', '1 juli 2013' ),
		);

		// Loop through some other languages
		$languageCodes = array( 'war', 'ceb', 'uk', 'ru', 'de' );
		$en = Language::factory( 'en' );

		foreach ( $languageCodes as $languageCode ) {
			$language = Language::factory( $languageCode );

			for ( $i = 1; $i <= 12; $i++ ) {
				$expected = $en->getMonthName( $i );

				$testCases[] = array( $language->getMonthName( $i ), $languageCode, $expected );
				$testCases[] = array( $language->getMonthNameGen( $i ), $languageCode, $expected );
				$testCases[] = array( $language->getMonthAbbreviation( $i ), $languageCode, $expected );
			}
		}

		return $testCases;
	}

	/**
	 * @dataProvider localizedDateProvider
	 * @param string $date
	 * @param string $languageCode
	 * @param string $expected
	 */
	public function testUnlocalize( $date, $languageCode, $expected ) {
		$unlocalizer = new MonthNameUnlocalizer();
		$actual = $unlocalizer->unlocalize( $date, $languageCode );

		$this->assertEquals( $expected, $actual );
	}

	public function languageChainProvider() {
		return array(
			// First language contains the word.
			array( 'Feb.', array( 'de', 'la' ), 'February' ),

			// Second language contains the word.
			array( 'February', array( 'de', 'en' ), 'February' ),
			array( 'Februar', array( 'en', 'de' ), 'February' ),
			array( 'Feb', array( 'de', 'la' ), 'February' ),
			array( 'Jun', array( 'de', 'ms' ), 'June' ),

			// No language contains the word.
			array( 'Jun', array( 'de', 'la' ), 'Jun' ),
		);
	}

	/**
	 * @dataProvider languageChainProvider
	 * @param string $date
	 * @param array $languageCodes
	 * @param string $expected
	 */
	public function testUnlocalize_withLanguageChains( $date, array $languageCodes, $expected ) {
		$unlocalizer = new MonthNameUnlocalizer();

		foreach ( $languageCodes as $languageCode ) {
			$date = $unlocalizer->unlocalize( $date, $languageCode );
		}

		$this->assertEquals( $expected, $date );
	}

}
