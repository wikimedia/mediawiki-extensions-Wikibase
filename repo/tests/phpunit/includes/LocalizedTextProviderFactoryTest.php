<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\LocalizedTextProviderFactory;

/**
 * @covers \Wikibase\Repo\LocalizedTextProviderFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LocalizedTextProviderFactoryTest extends MediaWikiIntegrationTestCase {
	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testGetForLanguageCode( string $langCode ): void {
		$factory = new LocalizedTextProviderFactory(
			MediaWikiServices::getInstance()->getLanguageFactory()
		);

		$localizer = $factory->getForLanguageCode( $langCode );

		$this->assertSame( $langCode, $localizer->getLanguageOf( '' ) );
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testGetForLanguage( string $langCode ): void {
		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();
		$factory = new LocalizedTextProviderFactory( $languageFactory );

		$localizer = $factory->getForLanguage( $languageFactory->getLanguage( $langCode ) );

		$this->assertSame( $langCode, $localizer->getLanguageOf( '' ) );
	}

	public function languageCodeProvider(): iterable {
		yield 'Creates localizer for English' => [ 'en' ];
		yield 'Creates localizer for German' => [ 'de' ];
	}
}
