<?php

namespace Wikibase\Test;

use Language;
use MWException;
use Wikibase\LanguageWithConversion;

/**
 * @covers Wikibase\LanguageWithConversion
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Liangent
 */
class LanguageWithConversionTest extends \MediaWikiTestCase {

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
		$this->assertEquals( $expectedLangCode, $obj->getLanguage()->getCode() );
		$this->assertEquals( $expectedLangCode, $obj->getLanguageCode() );
		if ( $expectedSourceLangCode === null ) {
			$this->assertNull( $obj->getSourceLanguage() );
			$this->assertNull( $obj->getSourceLanguageCode() );
		} else {
			$this->assertEquals( $expectedSourceLangCode, $obj->getSourceLanguage()->getCode() );
			$this->assertEquals( $expectedSourceLangCode, $obj->getSourceLanguageCode() );
		}
		$this->assertEquals( $expectedFetchLangCode, $obj->getFetchLanguage()->getCode() );
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
		$obj = LanguageWithConversion::factory( Language::factory( $langCode ),
			$sourceLangCode === null ? null : Language::factory( $sourceLangCode ) );
		$this->assertLanguageWithConversion( $obj,
			$expectedLangCode, $expectedSourceLangCode, $expectedFetchLangCode
		);
	}

	public function provideFactory() {
		return array(
			array( 'en', null, 'en', null, 'en' ),
			array( 'zh', null, 'zh', null, 'zh' ),
			array( 'zh-classical', null, 'lzh', null, 'lzh' ),
			array( 'zh-cn', null, 'zh-cn', null, 'zh-cn' ),
			array( 'zh', 'zh-cn', 'zh', 'zh-cn', 'zh-cn' ),
			array( 'zh-cn', 'zh', 'zh-cn', 'zh', 'zh' ),
			array( 'zh-cn', 'zh-tw', 'zh-cn', 'zh-tw', 'zh-tw' ),
		);
	}

	/**
	 * @dataProvider provideFactoryException
	 * @expectedException MWException
	 */
	public function testFactoryCodeException( $langCode, $sourceLangCode ) {
		LanguageWithConversion::factory( $langCode, $sourceLangCode );
	}

	/**
	 * @dataProvider provideFactoryException
	 * @expectedException MWException
	 */
	public function testFactoryException( $langCode, $sourceLangCode ) {
		LanguageWithConversion::factory( Language::factory( $langCode ),
			$sourceLangCode === null ? null : Language::factory( $sourceLangCode ) );
	}

	public function provideFactoryException() {
		return array(
			array( ':', null ),
			array( '/', null ),
			array( '/', ':' ),
			array( 'en', '/' ),
			array( 'en', 'de' ),
			array( 'en', 'en-gb' ),
			array( 'en-gb', 'en' ),
			array( 'de', 'de-formal' ),
			array( 'zh', 'en' ),
			array( 'zh-cn', 'zh-classical' ),
			array( 'zh', 'sr' ),
			array( 'zh-cn', 'en-gb' ),
			array( 'zh-tw', 'sr-ec' ),
		);
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
			$obj->prepareForTranslate( $text );
		}
		foreach ( $translations as $text => $translatedText ) {
			$this->assertEquals( $obj->translate( $text ), $translatedText );
		}
	}

	/**
	 * @dataProvider provideTranslate
	 */
	public function testReverseTranslate( $langCode, $sourceLangCode, $translations ) {
		if ( $sourceLangCode === null ) {
			$sourceLangCode = $langCode;
			$langCode = null;
		}
		$obj = LanguageWithConversion::factory( $sourceLangCode, $langCode );
		foreach ( $translations as $text => $translatedText ) {
			$this->assertEquals( $obj->reverseTranslate( $text ), $translatedText );
		}
	}

	public function provideTranslate() {
		return array(
			array( 'de', null, array(
				'foo' => 'foo',
				'bar' => 'bar',
			) ),
			array( 'zh', null, array(
				'測試' => '測試',
				'测试' => '测试',
			) ),
			array( 'zh-cn', null, array(
				'測試' => '測試',
				'测试' => '测试',
			) ),
			array( 'zh-cn', 'zh-tw', array(
				'測試' => '测试',
			) ),
			array( 'zh-tw', 'zh-cn', array(
				'測試' => '測試',
				'测试' => '測試',
				'測-{}-試' => '測-{}-試',
				'-{测试}-' => '-{測試}-',
				'测-{1}-试' => '測-{1}-試',
			) ),
			array( 'zh', 'zh', array(
				'測試' => '測試',
				'测试' => '测试',
				'測-{}-試' => '測-{}-試',
				'-{测试}-' => '-{测试}-',
				'测-{1}-试' => '测-{1}-试',
			) ),
		);
	}

}
