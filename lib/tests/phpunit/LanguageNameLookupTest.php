<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests;

use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Wikibase\Lib\LanguageNameLookup;

/**
 * @covers \Wikibase\Lib\LanguageNameLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageNameLookupTest extends MediaWikiIntegrationTestCase {

	public static function getNameProvider() {
		return [
			'en autonym' => [
				'en',
				LanguageNameUtils::AUTONYMS,
				'English',
			],
			'de autonym' => [
				'de',
				LanguageNameUtils::AUTONYMS,
				'Deutsch',
			],
			'en in de' => [
				'en',
				'de',
				'Englisch',
			],
			'de in en' => [
				'de',
				'en',
				'German',
			],
		];
	}

	/**
	 * @dataProvider getNameProvider
	 */
	public function testGetName( string $lang, ?string $in, string $expected ) {
		if ( $in !== LanguageNameUtils::AUTONYMS ) {
			$this->markTestSkippedIfExtensionNotLoaded( 'CLDR' );
		}

		$languageNameLookup = new LanguageNameLookup(
			MediaWikiServices::getInstance()->getLanguageNameUtils(),
			$in
		);
		$name = $languageNameLookup->getName( $lang );
		$this->assertSame( $expected, $name );
	}

}
