<?php

namespace Wikibase\Lib\Tests;

use Language;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\ContentLanguagesLanguageFallbackChainFilterer;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use InvalidArgumentException;
use Wikibase\Lib\StaticContentLanguages;

/**
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class ContentLanguagesLanguageFallbackChainFiltererTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testGetFallbackChain_returnsFallbackChainWithOnlyContentLanguages() {
		$filterer = new ContentLanguagesLanguageFallbackChainFilterer();
		$contentLanguages = new StaticContentLanguages( [ 'en', 'de' ] );
		$backupLang = Language::factory( 'en' );
		$fallbackChain = new LanguageFallbackChain( [
			LanguageWithConversion::factory( 'fr' ),
			LanguageWithConversion::factory( 'en' ),
			LanguageWithConversion::factory( 'es' ),
			LanguageWithConversion::factory( 'de' ),
		] );
		$resultingChain = $filterer->getFallbackChain( $contentLanguages, $fallbackChain, $backupLang );
		$this->assertEquals(
			[
			LanguageWithConversion::factory( 'en' ),
			LanguageWithConversion::factory( 'de' ),
			],
			array_values( $resultingChain->getFallbackChain() )
		);
	}

	public function testGetFallBackChain_returnsBackupLanguageFallbackChainIfWholeChangeIsFilteredOut() {
		$filterer = new ContentLanguagesLanguageFallbackChainFilterer();
		$contentLanguages = new StaticContentLanguages( [ 'en' ] );
		$backupLang = Language::factory( 'en' );
		$fallbackChain = new LanguageFallbackChain( [
			LanguageWithConversion::factory( 'fr' ),
		] );
		$resultingChain = $filterer->getFallbackChain( $contentLanguages, $fallbackChain, $backupLang );
		$this->assertEquals(
			[ LanguageWithConversion::factory( 'en' ) ],
			$resultingChain->getFallbackChain()
		);
	}

	public function testGetFallBackChain_throwsIfBackupNotContentLanguage() {
		$filterer = new ContentLanguagesLanguageFallbackChainFilterer();
		$contentLanguages = new StaticContentLanguages( [ 'en' ] );
		$backupLang = Language::factory( 'foobarlang' );
		$fallbackChain = $this->createMock( LanguageFallbackChain::class );

		$this->expectException( InvalidArgumentException::class );
		$filterer->getFallbackChain( $contentLanguages, $fallbackChain, $backupLang );
	}

}
