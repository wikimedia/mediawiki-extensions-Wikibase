<?php

namespace Wikibase\Test;
use Wikibase\Utils;

/**
 * Tests for the Wikibase\Utils class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseUtils
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class UtilsTest extends \MediaWikiTestCase {

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerGetLanguageCodes
	 */
	public function testGetLanguageCodes( $lang ) {
		$result = Utils::getLanguageCodes();
		$this->assertContains(
			$lang,
			$result,
			"The language code {$lang} could not be found in the returned result"
		);
	}

	public static function providerGetLanguageCodes() {
		return array(
			array( 'de' ),
			array( 'en' ),
			array( 'nb' ),
			array( 'nn' ),
		);
	}

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerGetLanguageCodesInvalid
	 */
	public function testGetLanguageCodesInvalid( $lang ) {
		$result = Utils::getLanguageCodes();
		$this->assertNotContains(
			$lang,
			$result,
			"The language code {$lang} is found in the returned result"
		);
	}

	public static function providerGetLanguageCodesInvalid() {
		return array(
			array( 'invalid' ),
			array( 'no' ),
			array( 'qqq' ),
			array( 'qqx' ),
			array( 'zh-classical' ),
		);
	}

	public static function provideFetchLanguageName() {
		return array(
			array( // #0
				'en',
				null,
				'English'
			),
			array( // #1
				'de',
				null,
				'Deutsch'
			),
			array( // #2
				'en',
				'de',
				'Englisch'
			),
			array( // #3
				'de',
				'en',
				'German'
			),
		);
	}

	/**
	 * @dataProvider provideFetchLanguageName
	 */
	public function testFetchLanguageName( $lang, $in, $expected ) {
		if ( $in !== null && !defined('CLDR_VERSION') ) {
			$this->markTestSkipped( "CLDR extension required for full language name support" );
		}

		$name = Utils::fetchLanguageName( $lang, $in );
		$this->assertEquals( $expected, $name );
	}

}
