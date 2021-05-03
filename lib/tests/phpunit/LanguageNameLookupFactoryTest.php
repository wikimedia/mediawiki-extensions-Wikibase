<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests;

use ExtensionRegistry;
use Language;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\LanguageNameLookupFactory;

/**
 * @covers \Wikibase\Lib\LanguageNameLookupFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageNameLookupFactoryTest extends TestCase {

	public function testForLanguage(): void {
		$this->requireCldr();

		$language = $this->createMock( Language::class );
		$language->expects( $this->once() )
			->method( 'getCode' )
			->willReturn( 'de' );
		$languageNameLookupFactory = new LanguageNameLookupFactory();

		$languageNameLookup = $languageNameLookupFactory->getForLanguage( $language );

		$this->assertSame( 'Englisch', $languageNameLookup->getName( 'en' ) );
	}

	public function testForLanguageCode(): void {
		$this->requireCldr();

		$languageNameLookupFactory = new LanguageNameLookupFactory();

		$languageNameLookup = $languageNameLookupFactory->getForLanguageCode( 'de' );

		$this->assertSame( 'Englisch', $languageNameLookup->getName( 'en' ) );
	}

	private function requireCldr(): void {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'CLDR' ) ) {
			$this->markTestSkipped( 'CLDR extension required for full language name support' );
		}
	}

}
