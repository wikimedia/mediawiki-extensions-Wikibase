<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests;

use MediaWiki\Language\Language;
use MediaWiki\Registration\ExtensionRegistry;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\MediaWikiMessageInLanguageProvider;

/**
 * @covers \Wikibase\Lib\LanguageNameLookupFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageNameLookupFactoryTest extends \MediaWikiIntegrationTestCase {

	public function testForLanguage(): void {
		if ( !(
			ExtensionRegistry::getInstance()->isLoaded( 'cldr' )
			|| ExtensionRegistry::getInstance()->isLoaded( 'CLDR' )
		) ) {
			self::markTestSkipped( 'cldr extension is required for this test' );
		}

		$language = $this->createMock( Language::class );
		$language->expects( $this->once() )
			->method( 'getCode' )
			->willReturn( 'de' );
		$languageNameLookupFactory = new LanguageNameLookupFactory(
			$this->getServiceContainer()->getLanguageNameUtils(),
			new MediaWikiMessageInLanguageProvider()
		);

		$languageNameLookup = $languageNameLookupFactory->getForLanguage( $language );

		$this->assertSame( 'Englisch',
			$languageNameLookup->getName( 'en' ) );
	}

	public function testForLanguageCode(): void {
		if ( !(
			ExtensionRegistry::getInstance()->isLoaded( 'cldr' )
			|| ExtensionRegistry::getInstance()->isLoaded( 'CLDR' )
		) ) {
			self::markTestSkipped( 'cldr extension is required for this test' );
		}

		$languageNameLookupFactory = new LanguageNameLookupFactory(
			$this->getServiceContainer()->getLanguageNameUtils(),
			new MediaWikiMessageInLanguageProvider()
		);

		$languageNameLookup = $languageNameLookupFactory->getForLanguageCode( 'de' );

		$this->assertSame( 'Englisch',
			$languageNameLookup->getName( 'en' ) );
	}

	public function testForAutonyms(): void {
		$languageNameLookupFactory = new LanguageNameLookupFactory(
			$this->getServiceContainer()->getLanguageNameUtils(),
			new MediaWikiMessageInLanguageProvider()
		);

		$languageNameLookup = $languageNameLookupFactory->getForAutonyms();

		$this->assertSame( 'English',
			$languageNameLookup->getName( 'en' ) );
	}

}
