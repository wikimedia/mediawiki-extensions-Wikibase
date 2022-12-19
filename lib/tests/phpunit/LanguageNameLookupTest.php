<?php

namespace Wikibase\Lib\Tests;

use MediaWiki\MediaWikiServices;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @covers \Wikibase\Lib\LanguageNameLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageNameLookupTest extends \PHPUnit\Framework\TestCase {

	public function getNameProvider() {
		return [
			[ // #0
				'en',
				null,
				'English',
			],
			[ // #1
				'de',
				null,
				'Deutsch',
			],
			[ // #2
				'en',
				'de',
				'Englisch',
			],
			[ // #3
				'de',
				'en',
				'German',
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

		$languageNameLookup = new LanguageNameLookup(
			MediaWikiServices::getInstance()->getLanguageNameUtils(),
			$in
		);
		$name = $languageNameLookup->getName( $lang );
		$this->assertSame( $expected, $name );
	}

}
