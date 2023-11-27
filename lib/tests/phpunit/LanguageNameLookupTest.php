<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests;

use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\MediaWikiMessageInLanguageProvider;

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
			new MediaWikiMessageInLanguageProvider(),
			$in
		);
		$name = $languageNameLookup->getName( $lang );
		$this->assertSame( $expected, $name );
	}

	/** @dataProvider getNameProvider */
	public function testGetNameForTerms_notMul( string $lang, ?string $in, string $expected ): void {
		if ( $in !== LanguageNameUtils::AUTONYMS ) {
			$this->markTestSkippedIfExtensionNotLoaded( 'CLDR' );
		}

		$languageNameLookup = new LanguageNameLookup(
			MediaWikiServices::getInstance()->getLanguageNameUtils(),
			new MediaWikiMessageInLanguageProvider(),
			$in
		);
		$name = $languageNameLookup->getNameForTerms( $lang );
		$this->assertSame( $expected, $name );
	}

	public function testGetNameForTerms_mul(): void {
		$languageNameLookup = new LanguageNameLookup(
			MediaWikiServices::getInstance()->getLanguageNameUtils(),
			new MediaWikiMessageInLanguageProvider(),
			'en'
		);
		$name = $languageNameLookup->getNameForTerms( 'mul' );
		$this->assertSame( 'default values (mul)', $name );
	}

}
