<?php

namespace Wikibase\Test;

use Language;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\LanguageWithConversion;

/**
 * @covers Wikibase\LanguageFallbackChain
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0+
 * @author Liangent < liangent@gmail.com >
 * @author Thiemo Mättig
 */
class LanguageFallbackChainTest extends \MediaWikiTestCase {

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
		$data = array(
			'en' => 'foo',
			'nl' => 'bar',
			'zh-cn' => '测试',
			'lzh' => '試',
			'zh-classical' => '驗',
		);
		$entityInfoBuilderArray = array(
			'de' => array(
				'language' => 'de',
				'value' => 'Beispiel'
			),
			'zh-cn' => array(
				'language' => 'zh-cn',
				'value' => '测试'
			)
		);

		return array(
			array( 'en', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			) ),
			array( 'zh-classical', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => '試',
				'language' => 'lzh',
				'source' => null,
			) ),
			array( 'nl', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => 'bar',
				'language' => 'nl',
				'source' => null,
			) ),
			array( 'de', LanguageFallbackChainFactory::FALLBACK_SELF, $data, null ),
			array( 'de', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			) ),
			array( 'zh', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => '测试',
				'language' => 'zh',
				'source' => 'zh-cn',
			) ),
			array( 'zh-tw', LanguageFallbackChainFactory::FALLBACK_SELF, $data, null ),
			array( 'zh-tw', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => '測試',
				'language' => 'zh-tw',
				'source' => 'zh-cn',
			) ),
			array(
				'zh-tw',
				LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				$data,
				array(
					'value' => '測試',
					'language' => 'zh-tw',
					'source' => 'zh-cn',
				),
			),
			array(
				'sr-cyrl',
				LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				$data,
				null,
			),
			array( 'sr-cyrl', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				// Shouldn't be converted to Cyrillic ('фоо') as this specific
				// value ('foo') is taken from the English label.
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			) ),
			array(
				'gan-hant',
				LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				$data,
				null,
			),
			array( 'gan-hant', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => '測試',
				'language' => 'zh-hant',
				'source' => 'zh-cn',
			) ),

			array( 'de', LanguageFallbackChainFactory::FALLBACK_SELF, $entityInfoBuilderArray, array(
				'value' => 'Beispiel',
				'language' => 'de',
				'source' => null,
			) ),
			array( 'gan-hant', LanguageFallbackChainFactory::FALLBACK_ALL, $entityInfoBuilderArray, array(
				'value' => '測試',
				'language' => 'zh-hant',
				'source' => 'zh-cn',
			) ),
		);
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
		$data = array(
			'en' => 'foo',
			'nl' => 'bar',
			'zh-cn' => '测试',
		);
		$entityInfoBuilderArray = array(
			'en' => array(
				'language' => 'en',
				'value' => 'Example'
			),
		);

		return array(
			array( 'en', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			) ),
			array( 'nl', LanguageFallbackChainFactory::FALLBACK_ALL, $data, array(
				'value' => 'bar',
				'language' => 'nl',
				'source' => null,
			) ),
			array( 'de', LanguageFallbackChainFactory::FALLBACK_SELF, $data, array(
				'value' => 'foo',
				'language' => 'en',
				'source' => null,
			) ),

			array( 'fr', LanguageFallbackChainFactory::FALLBACK_SELF, array(
				'kk' => 'baz',
			), array(
				'value' => 'baz',
				'language' => 'kk',
				'source' => null,
			) ),
			array( 'it', LanguageFallbackChainFactory::FALLBACK_SELF, array(
				':' => 'qux',
				'kk' => 'baz',
			), array(
				'value' => 'baz',
				'language' => 'kk',
				'source' => null,
			) ),
			array( 'sr', LanguageFallbackChainFactory::FALLBACK_SELF, array(
				':' => 'qux',
			), null ),
			array( 'en', LanguageFallbackChainFactory::FALLBACK_ALL, array(), null ),
			array( 'ar', LanguageFallbackChainFactory::FALLBACK_SELF, array(), null ),

			array( 'de', LanguageFallbackChainFactory::FALLBACK_SELF, $entityInfoBuilderArray, array(
				'value' => 'Example',
				'language' => 'en',
			) ),
		);
	}

	public function provideFetchLanguageCodes() {
		return array(
			'empty' => array( array() ),
			'de-ch' => array( array( 'de-ch', 'de', 'en' ) ),
			'zh' => array( array( 'zh-hans', 'zh-hant', 'zh-cn', 'zh-tw', 'zh-hk', 'zh-sg', 'zh-mo', 'zh-my', 'en' ) ),
		);
	}

	/**
	 * @dataProvider provideFetchLanguageCodes
	 */
	public function testGetFetchLanguageCodes( array $languages ) {
		$languagesWithConversion = array();

		foreach ( $languages as $language ) {
			$languagesWithConversion[] = LanguageWithConversion::factory( $language );
		}

		$chain = new LanguageFallbackChain( $languagesWithConversion );

		$codes = $chain->getFetchLanguageCodes();
		$this->assertEquals( $languages, $codes );
	}

}
