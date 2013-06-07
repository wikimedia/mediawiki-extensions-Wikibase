<?php

namespace Wikibase\Test;
use Wikibase\LanguageWrapper;
use \Language;

/**
 * Tests for the Wikibase\LanguageWrapper class.
 *
 * @file
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 */
class LanguageWrapperTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider provideFactory
	 */
	public function testFactory( $langCode, $sourceLangCode, $expectedLangCode, $expectedFetchLangCode ) {
		$obj = LanguageWrapper::factory( Language::factory( $langCode ),
			$sourceLangCode === null ? null : Language::factory( $sourceLangCode ) );
		$this->assertEquals( $obj->getLanguage()->getCode(), $expectedLangCode );
		$this->assertEquals( $obj->getFetchLanguage()->getCode(), $expectedFetchLangCode );
	}

	public function provideFactory() {
		return array(
			array( 'en', null, 'en', 'en' ),
			array( 'zh', null, 'zh', 'zh' ),
			array( 'zh-cn', null, 'zh-cn', 'zh-cn' ),
			array( 'zh', 'zh-cn', 'zh', 'zh-cn' ),
			array( 'zh-cn', 'zh', 'zh-cn', 'zh' ),
			array( 'zh-cn', 'zh-tw', 'zh-cn', 'zh-tw' ),
		);
	}

	/**
	 * @dataProvider provideFactoryException
	 * @expectedException MWException
	 */
	public function testFactoryException( $langCode, $sourceLangCode ) {
		LanguageWrapper::factory( Language::factory( $langCode ),
			$sourceLangCode === null ? null : Language::factory( $sourceLangCode ) );
	}

	public function provideFactoryException() {
		return array(
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
		$obj = LanguageWrapper::factory( Language::factory( $langCode ),
			$sourceLangCode === null ? null : Language::factory( $sourceLangCode ) );
		foreach ( $translations as $text => $translatedText ) {
			$this->assertEquals( $obj->translate( $text ), $translatedText );
		}
	}

	/**
	 * @dataProvider provideTranslate
	 */
	public function testTranslateBatched( $langCode, $sourceLangCode, $translations ) {
		$obj = LanguageWrapper::factory( Language::factory( $langCode ),
			$sourceLangCode === null ? null : Language::factory( $sourceLangCode ) );
		foreach ( $translations as $text => $translatedText ) {
			$obj->prepareForTranslate( $text );
		}
		foreach ( $translations as $text => $translatedText ) {
			$this->assertEquals( $obj->translate( $text ), $translatedText );
		}
	}

	public function provideTranslate() {
		return array(
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
