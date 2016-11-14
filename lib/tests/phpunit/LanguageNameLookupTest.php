<?php

namespace Wikibase\Lib\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @covers Wikibase\Lib\LanguageNameLookup
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Marius Hoch < hoo@online.sde >
 */
class LanguageNameLookupTest extends PHPUnit_Framework_TestCase {

	public function getNameProvider() {
		return [
			[ // #0
				'en',
				null,
				'English'
			],
			[ // #1
				'de',
				null,
				'Deutsch'
			],
			[ // #2
				'en',
				'de',
				'Englisch'
			],
			[ // #3
				'de',
				'en',
				'German'
			],
		];
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
