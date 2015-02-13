<?php

namespace Wikibase\Test;

use Wikibase\Utils;

/**
 * @covers Wikibase\Utils
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

	public function provideFetchLanguageName() {
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
