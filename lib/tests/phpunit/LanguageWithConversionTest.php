<?php

namespace Wikibase\Lib\Tests;

use MediaWikiIntegrationTestCase;
use MWException;
use Wikibase\Lib\LanguageWithConversion;

/**
 * @covers \Wikibase\Lib\LanguageWithConversion
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Liangent < liangent@gmail.com >
 */
class LanguageWithConversionTest extends MediaWikiIntegrationTestCase {

	/**
	 * @param LanguageWithConversion $obj
	 * @param string $expectedLangCode
	 * @param string|null $expectedSourceLangCode
	 * @param string $expectedFetchLangCode
	 */
	private function assertLanguageWithConversion(
		LanguageWithConversion $obj,
		$expectedLangCode,
		$expectedSourceLangCode,
		$expectedFetchLangCode
	) {
		$this->assertEquals( $expectedLangCode, $obj->getLanguageCode() );
		if ( $expectedSourceLangCode === null ) {
			$this->assertNull( $obj->getSourceLanguageCode() );
		} else {
			$this->assertEquals( $expectedSourceLangCode, $obj->getSourceLanguageCode() );
		}
		$this->assertEquals( $expectedFetchLangCode, $obj->getFetchLanguageCode() );
	}

	/**
	 * @dataProvider provideFactory
	 */
	public function testFactoryCode( $langCode, $sourceLangCode,
		$expectedLangCode, $expectedSourceLangCode, $expectedFetchLangCode
	) {
		$obj = LanguageWithConversion::factory( $langCode, $sourceLangCode );
		$this->assertLanguageWithConversion( $obj,
			$expectedLangCode, $expectedSourceLangCode, $expectedFetchLangCode
		);
	}

	/**
	 * @dataProvider provideFactory
	 */
	public function testFactory( $langCode, $sourceLangCode,
		$expectedLangCode, $expectedSourceLangCode, $expectedFetchLangCode
	) {
		$obj = LanguageWithConversion::factory( $langCode, $sourceLangCode );
		$this->assertLanguageWithConversion( $obj,
			$expectedLangCode, $expectedSourceLangCode, $expectedFetchLangCode
		);
	}

	public function provideFactory() {
		return [
			[ 'en', null, 'en', null, 'en' ],
			[ 'zh', null, 'zh', null, 'zh' ],
			[ 'zh-classical', null, 'lzh', null, 'lzh' ],
			[ 'zh-cn', null, 'zh-cn', null, 'zh-cn' ],
			[ 'zh', 'zh-cn', 'zh', 'zh-cn', 'zh-cn' ],
			[ 'zh-cn', 'zh', 'zh-cn', 'zh', 'zh' ],
			[ 'zh-cn', 'zh-tw', 'zh-cn', 'zh-tw', 'zh-tw' ],
		];
	}

	/**
	 * @dataProvider provideFactoryException
	 */
	public function testFactoryCodeException( $langCode, $sourceLangCode ) {
		$this->expectException( MWException::class );
		LanguageWithConversion::factory( $langCode, $sourceLangCode );
	}

	/**
	 * @dataProvider provideFactoryException
	 */
	public function testFactoryException( $langCode, $sourceLangCode ) {
		$this->expectException( MWException::class );
		LanguageWithConversion::factory( $langCode, $sourceLangCode );
	}

	public function provideFactoryException() {
		return [
			[ ':', null ],
			[ '/', null ],
			[ '/', ':' ],
			[ 'en', '/' ],
			[ 'en', 'de' ],
			[ 'en', 'en-gb' ],
			[ 'en-gb', 'en' ],
			[ 'de', 'de-formal' ],
			[ 'zh', 'en' ],
			[ 'zh-cn', 'zh-classical' ],
			[ 'zh', 'sr' ],
			[ 'zh-cn', 'en-gb' ],
			[ 'zh-tw', 'sr-ec' ],
		];
	}

	/**
	 * @dataProvider provideTranslate
	 */
	public function testTranslate( $langCode, $sourceLangCode, $translations ) {
		$obj = LanguageWithConversion::factory( $langCode, $sourceLangCode );
		foreach ( $translations as $text => $translatedText ) {
			$this->assertEquals( $obj->translate( $text ), $translatedText );
		}
	}

	/**
	 * @dataProvider provideTranslate
	 */
	public function testTranslateBatched( $langCode, $sourceLangCode, $translations ) {
		$obj = LanguageWithConversion::factory( $langCode, $sourceLangCode );
		foreach ( $translations as $text => $translatedText ) {
			$this->assertEquals( $obj->translate( $text ), $translatedText );
		}
	}

	public function provideTranslate() {
		return [
			[ 'de', null, [
				'foo' => 'foo',
				'bar' => 'bar',
			] ],
			[ 'zh', null, [
				'測試' => '測試',
				'测试' => '测试',
			] ],
			[ 'zh-cn', null, [
				'測試' => '測試',
				'测试' => '测试',
			] ],
			[ 'zh-cn', 'zh-tw', [
				'測試' => '测试',
			] ],
			[ 'zh-tw', 'zh-cn', [
				'測試' => '測試',
				'测试' => '測試',
				'測-{}-試' => '測-{}-試',
				'-{测试}-' => '-{測試}-',
				'测-{1}-试' => '測-{1}-試',
			] ],
			[ 'zh', 'zh', [
				'測試' => '測試',
				'测试' => '测试',
				'測-{}-試' => '測-{}-試',
				'-{测试}-' => '-{测试}-',
				'测-{1}-试' => '测-{1}-试',
			] ],
		];
	}

}
