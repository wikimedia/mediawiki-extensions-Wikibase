<?php

namespace Wikibase\Lib\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @covers Wikibase\Lib\LanguageNameLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class LanguageNameLookupTest extends PHPUnit_Framework_TestCase {

	public function getNameProvider() {
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
	 * @dataProvider getNameProvider
	 */
	public function testGetName( $lang, $in, $expected ) {
		if ( $in !== null && !\ExtensionRegistry::getInstance()->isLoaded( 'CLDR' ) ) {
			$this->markTestSkipped( 'CLDR extension required for full language name support' );
		}

		$languageNameLookup = new LanguageNameLookup( $in );
		$name = $languageNameLookup->getName( $lang );
		$this->assertSame( $expected, $name );
	}

}
