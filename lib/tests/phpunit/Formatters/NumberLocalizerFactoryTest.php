<?php

namespace Wikibase\Lib\Tests\Formatters;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Formatters\NumberLocalizerFactory;

/**
 * @covers \Wikibase\Lib\Formatters\NumberLocalizerFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class NumberLocalizerFactoryTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testGetForLanguageCode( string $langCode, string $expected ): void {
		$factory = new NumberLocalizerFactory(
			MediaWikiServices::getInstance()->getLanguageFactory()
		);

		$localizer = $factory->getForLanguageCode( $langCode );

		$this->assertSame( $expected, $localizer->localizeNumber( 13.4 ) );
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testGetForLanguage( string $langCode, string $expected ): void {
		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();
		$factory = new NumberLocalizerFactory( $languageFactory );

		$localizer = $factory->getForLanguage( $languageFactory->getLanguage( $langCode ) );

		$this->assertSame( $expected, $localizer->localizeNumber( 13.4 ) );
	}

	public function languageCodeProvider(): iterable {
		yield 'Localizes numbers for English'
			=> [ 'en', '13.4' ];
		yield 'Localizes numbers for German'
			=> [ 'de', '13,4' ];
	}
}
