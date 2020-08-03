<?php

namespace Wikibase\Lib\Tests;

use Language;
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
class TermLanguageFallbackChainTest extends \MediaWikiTestCase {

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

	/**
	 * @dataProvider provideExtractPreferredValue
	 */
	public function testExtractPreferredValue( $languageCode, $mode, $data, $expected ) {
		$factory = new LanguageFallbackChainFactory();
		$chain = $factory->newFromLanguageCode( $languageCode, $mode );

		$resolved = $chain->extractPreferredValue( $data );

		$this->assertEquals( $expected, $resolved );
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
				'value' => 'Beispiel'
			],
			'zh-cn' => [
				'language' => 'zh-cn',
				'value' => '测试'
			]
		];

		return [
			[ 'en', LanguageFallbackChainFactory::FALLBACK_ALL, $data, [
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			] ],
			[ 'zh-classical', LanguageFallbackChainFactory::FALLBACK_ALL, $data, [
				'value' => '試',
				'language' => 'lzh',
				'source' => null,
			] ],
			[ 'nl', LanguageFallbackChainFactory::FALLBACK_ALL, $data, [
				'value' => 'bar',
				'language' => 'nl',
				'source' => null,
			] ],
			[ 'de', LanguageFallbackChainFactory::FALLBACK_SELF, $data, null ],
			[ 'de', LanguageFallbackChainFactory::FALLBACK_ALL, $data, [
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			] ],
			[ 'zh', LanguageFallbackChainFactory::FALLBACK_ALL, $data, [
				'value' => '测试',
				'language' => 'zh',
				'source' => 'zh-cn',
			] ],
			[ 'zh-tw', LanguageFallbackChainFactory::FALLBACK_SELF, $data, null ],
			[ 'zh-tw', LanguageFallbackChainFactory::FALLBACK_ALL, $data, [
				'value' => '測試',
				'language' => 'zh-tw',
				'source' => 'zh-cn',
			] ],
			[
				'zh-tw',
				LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				$data,
				[
					'value' => '測試',
					'language' => 'zh-tw',
					'source' => 'zh-cn',
				],
			],
			[
				'kk-cyrl',
				LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				$data,
				null,
			],
			[ 'kk-cyrl', LanguageFallbackChainFactory::FALLBACK_ALL, $data, [
				// Shouldn't be converted to Cyrillic ('фоо') as this specific
				// value ('foo') is taken from the English label.
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			] ],
			[
				'gan-hant',
				LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				$data,
				null,
			],
			[ 'gan-hant', LanguageFallbackChainFactory::FALLBACK_ALL, $data, [
				'value' => '測試',
				'language' => 'zh-hant',
				'source' => 'zh-cn',
			] ],

			[ 'de', LanguageFallbackChainFactory::FALLBACK_SELF, $entityInfoBuilderArray, [
				'value' => 'Beispiel',
				'language' => 'de',
				'source' => null,
			] ],
			[ 'gan-hant', LanguageFallbackChainFactory::FALLBACK_ALL, $entityInfoBuilderArray, [
				'value' => '測試',
				'language' => 'zh-hant',
				'source' => 'zh-cn',
			] ],
		];
	}

	/**
	 * @dataProvider provideExtractPreferredValueOrAny
	 */
	public function testExtractPreferredValueOrAny( $languageCode, $mode, $data, $expected ) {
		$factory = new LanguageFallbackChainFactory();
		$chain = $factory->newFromLanguage( Language::factory( $languageCode ), $mode );

		$resolved = $chain->extractPreferredValueOrAny( $data );

		$this->assertEquals( $expected, $resolved );
	}

	public function provideExtractPreferredValueOrAny() {
		$data = [
			'en' => 'foo',
			'nl' => 'bar',
			'zh-cn' => '测试',
		];
		$entityInfoBuilderArray = [
			'en' => [
				'language' => 'en',
				'value' => 'Example'
			],
		];

		return [
			[ 'en', LanguageFallbackChainFactory::FALLBACK_ALL, $data, [
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			] ],
			[ 'nl', LanguageFallbackChainFactory::FALLBACK_ALL, $data, [
				'value' => 'bar',
				'language' => 'nl',
				'source' => null,
			] ],
			[ 'de', LanguageFallbackChainFactory::FALLBACK_SELF, $data, [
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			] ],

			[ 'fr', LanguageFallbackChainFactory::FALLBACK_SELF, [
				'kk' => 'baz',
			], [
				'value' => 'baz',
				'language' => 'kk',
				'source' => null,
			] ],
			[ 'it', LanguageFallbackChainFactory::FALLBACK_SELF, [
				':' => 'qux',
				'kk' => 'baz',
			], [
				'value' => 'baz',
				'language' => 'kk',
				'source' => null,
			] ],
			[ 'sr', LanguageFallbackChainFactory::FALLBACK_SELF, [
				':' => 'qux',
			], null ],
			[ 'en', LanguageFallbackChainFactory::FALLBACK_ALL, [], null ],
			[ 'ar', LanguageFallbackChainFactory::FALLBACK_SELF, [], null ],

			[ 'de', LanguageFallbackChainFactory::FALLBACK_SELF, $entityInfoBuilderArray, [
				'value' => 'Example',
				'language' => 'en',
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
