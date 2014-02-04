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
 */
class MonthNameUnlocalizerTest extends \PHPUnit_Framework_TestCase {

	public function provideUnlocalize() {
		$testCases = array(

			//Should unlocalize dates
			array( '1 Juli 2013', 'de', '1 July 2013' ),
			array( '1 Januarie 1999', 'af', '1 January 1999' ),
			array( '1 Jan 1999', 'af', '1 January 1999' ),
			array( '16 Jenna 1999', 'bar', '16 January 1999' ),

			//Shouldn#t do anything if we cant / don#t need to
			array( '1 June 2013', 'en', '1 June 2013' ),
			array( '1 Jan 2013', 'en', '1 Jan 2013' ),
			array( '16 FooBarBarxxx 1999', 'bar', '16 FooBarBarxxx 1999' ),

		);

		//Loop through some other languages
		$someLangs = array( 'war', 'ceb', 'uk', 'ru', 'de' );
		$en = Language::factory( 'en' );

		foreach( $someLangs as $from ) {
			$fromLang = Language::factory( $from );
			for ( $i = 1; $i <= 12; $i++ ) {
				$testCases[] = array( $fromLang->getMonthName( $i ), $from, $en->getMonthName( $i ) );
				$testCases[] = array( $fromLang->getMonthAbbreviation( $i ), $from, $en->getMonthName( $i ) );
			}
		}

		return $testCases;
	}

	/**
	 * @dataProvider provideUnlocalize
	 *
	 * @param $localized
	 * @param $lang
	 * @param $expected
	 */
	public function testUnlocalize( $localized, $lang, $expected ) {
		$monthUnlocalizer = new MonthNameUnlocalizer();
		$options = new ParserOptions();

		$actual = $monthUnlocalizer->unlocalize( $localized, $lang, $options );

		$this->assertEquals( $expected, $actual );
	}

}