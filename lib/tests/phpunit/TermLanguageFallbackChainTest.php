<?php

namespace Wikibase\Lib\Tests;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageWithConversion;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * @covers \Wikibase\Lib\TermLanguageFallbackChain
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Liangent < liangent@gmail.com >
 * @author Thiemo Kreuz
 */
class TermLanguageFallbackChainTest extends MediaWikiIntegrationTestCase {

	public function testFilteringOutInvalidLanguages() {
		$chainOfTestLanguageCodes = [
			'123',
			'notALanguage',
			'()',
			'@',
			'de',
			'🏄',
			'en',
			'',
			'⧼Lang⧽',
		];
		$chainOfLanguages = array_map( function ( string $langCode ) {
			return LanguageWithConversion::factory( $langCode );
		}, $chainOfTestLanguageCodes );
		$stubContentLanguages = $this->createStub( ContentLanguages::class );
		$stubContentLanguages->method( 'hasLanguage' )->willReturnCallback(
			function ( $langCode ) {
				return $langCode === 'de' || $langCode === 'en';
			}
		);
		$chain = new TermLanguageFallbackChain( $chainOfLanguages, $stubContentLanguages );
		$actualFallbackChain = array_map( function ( LanguageWithConversion $lang ) {
			return $lang->getLanguageCode();
		},
			$chain->getFallbackChain() );

		$this->assertSame( [ 'de', 'en' ], $actualFallbackChain );
	}

	public function testChainNotBeingUnintentionallyEmpty() {
		$stubContentLanguages = $this->createStub( ContentLanguages::class );
		$stubContentLanguages->method( 'hasLanguage' )->willReturn( false );
		$chain = new TermLanguageFallbackChain( [ LanguageWithConversion::factory( '⧼Lang⧽' ) ], $stubContentLanguages );
		$actualFallbackChain = array_map( function ( LanguageWithConversion $lang ) {
			return $lang->getLanguageCode();
		},
			$chain->getFallbackChain() );

		$this->assertSame( [ 'en' ], $actualFallbackChain );
	}

	/**
	 * @dataProvider provideExtractPreferredValue
	 */
	public function testExtractPreferredValue( $languageCode, $data, $expected ) {
		$factory = new LanguageFallbackChainFactory();
		$chain = $factory->newFromLanguageCode( $languageCode );

		$resolved = $chain->extractPreferredValue( $data );

		$this->assertSame( $expected, $resolved );
	}

	public function provideExtractPreferredValue() {
		$data = [
			'en' => 'foo',
			'nl' => 'bar',
			'zh-cn' => '测试',
			'lzh' => '試',
			'zh-classical' => '驗',
		];
		$entityInfoBuilderArray = [
			'de' => [
				'language' => 'de',
				'value' => 'Beispiel',
			],
			'zh-cn' => [
				'language' => 'zh-cn',
				'value' => '测试',
			],
		];

		return [
			[ 'en', $data, [
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			] ],
			[ 'zh-classical', $data, [
				'value' => '試',
				'language' => 'lzh',
				'source' => null,
			] ],
			[ 'nl', $data, [
				'value' => 'bar',
				'language' => 'nl',
				'source' => null,
			] ],
			[ 'de', $data, [
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			] ],
			[ 'zh', $data, [
				'value' => '测试',
				'language' => 'zh',
				'source' => 'zh-cn',
			] ],
			[ 'zh-tw', $data, [
				'value' => '測試',
				'language' => 'zh-tw',
				'source' => 'zh-cn',
			] ],
			[ 'kk-cyrl', $data, [
				// Shouldn't be converted to Cyrillic ('фоо') as this specific
				// value ('foo') is taken from the English label.
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			] ],
			[ 'gan-hant', $data, [
				'value' => '測試',
				'language' => 'zh-hant',
				'source' => 'zh-cn',
			] ],

			[ 'de', $entityInfoBuilderArray, [
				'value' => 'Beispiel',
				'language' => 'de',
				'source' => null,
			] ],
			[ 'gan-hant', $entityInfoBuilderArray, [
				'value' => '測試',
				'language' => 'zh-hant',
				'source' => 'zh-cn',
			] ],
		];
	}

	/**
	 * @dataProvider provideExtractPreferredValueOrAny
	 */
	public function testExtractPreferredValueOrAny( $languageCode, $data, $expected ) {
		$factory = new LanguageFallbackChainFactory();
		$chain = $factory->newFromLanguage( $this->getServiceContainer()->getLanguageFactory()->getLanguage( $languageCode ) );

		$resolved = $chain->extractPreferredValueOrAny( $data );

		$this->assertEquals( $expected, $resolved );
	}

	public function provideExtractPreferredValueOrAny() {
		$data = [
			'en' => 'foo',
			'nl' => 'bar',
			'zh-cn' => '测试',
		];

		return [
			[ 'en', $data, [
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			] ],
			[ 'nl', $data, [
				'value' => 'bar',
				'language' => 'nl',
				'source' => null,
			] ],
			[ 'de', $data, [
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			] ],

			[ 'fr', [
				'kk' => 'baz',
			], [
				'value' => 'baz',
				'language' => 'kk',
				'source' => null,
			] ],
			[ 'it', [
				':' => 'qux',
				'kk' => 'baz',
			], [
				'value' => 'baz',
				'language' => 'kk',
				'source' => null,
			] ],
			[ 'sr', [
				':' => 'qux',
			], null ],
			[ 'en', [], null ],
			[ 'ar', [], null ],

			[ 'de', [
				'fr' => [
					'language' => 'fr',
					'value' => 'exemple',
				],
			], [
				'language' => 'fr',
				'value' => 'exemple',
			] ],
		];
	}

	public function provideFetchLanguageCodes() {
		return [
			'empty' => [ [] ],
			'de-ch' => [ [ 'de-ch', 'de', 'en' ] ],
			'zh' => [ [ 'zh-hans', 'zh-hant', 'zh-cn', 'zh-tw', 'zh-hk', 'zh-sg', 'zh-mo', 'zh-my', 'en' ] ],
		];
	}

	/**
	 * @dataProvider provideFetchLanguageCodes
	 */
	public function testGetFetchLanguageCodes( array $languages ) {
		$languagesWithConversion = [];

		foreach ( $languages as $language ) {
			$languagesWithConversion[] = LanguageWithConversion::factory( $language );
		}

		$contentLanguages = $this->createStub( ContentLanguages::class );
		$contentLanguages->method( 'hasLanguage' )->willReturn( true );
		$chain = new TermLanguageFallbackChain( $languagesWithConversion, $contentLanguages );

		$codes = $chain->getFetchLanguageCodes();
		$this->assertEquals( $languages, $codes );
	}

}
