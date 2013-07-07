<?php

namespace Wikibase\Test;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;

/**
 * Tests for the Wikibase\LanguageFallbackChainFactory class.
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseUtils
 *
 * @licence GNU GPL v2+
 */
class LanguageFallbackChainFactoryTest extends \MediaWikiTestCase {

	private function assertChainEquals( $expectedItems, $chain ) {
		$this->assertEquals( count( $expectedItems ), count( $chain ) );

		foreach ( $expectedItems as $i => $expected ) {
			if ( is_array( $expected ) ) {
				$this->assertEquals( $expected[0], $chain[$i]->getLanguage()->getCode() );
				$this->assertEquals( $expected[1], $chain[$i]->getSourceLanguage()->getCode() );
			} else {
				$this->assertEquals( $expected, $chain[$i]->getLanguage()->getCode() );
				$this->assertNull( $chain[$i]->getSourceLanguage() );
			}
		}
	}

	/**
	 * @group WikibaseLib
	 * @dataProvider providerNewFromLanguage
	 */
	public function testNewFromLanguage( $lang, $mode, $expected ) {
		$factory = new LanguageFallbackChainFactory();
		$chain = $factory->newFromLanguage( \Language::factory( $lang ), $mode )->getFallbackChain();
		$this->assertChainEquals( $expected, $chain );
	}

	/**
	 * @group WikibaseLib
	 * @dataProvider providerNewFromLanguage
	 */
	public function testNewFromLanguageCode( $lang, $mode, $expected ) {
		$factory = new LanguageFallbackChainFactory();
		$chain = $factory->newFromLanguageCode( $lang, $mode )->getFallbackChain();
		$this->assertChainEquals( $expected, $chain );
	}

	public static function providerNewFromLanguage() {
		return array(
			array( 'en', LanguageFallbackChainFactory::FALLBACK_ALL, array( 'en' ) ),
			array( 'en', LanguageFallbackChainFactory::FALLBACK_VARIANTS, array() ),
			array( 'en', LanguageFallbackChainFactory::FALLBACK_OTHERS, array() ),

			array( 'zh-classical', LanguageFallbackChainFactory::FALLBACK_SELF, array( 'lzh' ) ),

			array( 'de-formal', LanguageFallbackChainFactory::FALLBACK_ALL, array( 'de-formal', 'de', 'en' ) ),
			// Repeated to test caching
			array( 'de-formal', LanguageFallbackChainFactory::FALLBACK_ALL, array( 'de-formal', 'de', 'en' ) ),
			array( 'de-formal', LanguageFallbackChainFactory::FALLBACK_VARIANTS, array() ),
			array( 'de-formal', ~LanguageFallbackChainFactory::FALLBACK_SELF, array( 'de', 'en' ) ),

			array( 'zh', LanguageFallbackChainFactory::FALLBACK_ALL, array(
				'zh',
				array( 'zh', 'zh-hans' ),
				array( 'zh', 'zh-hant' ),
				array( 'zh', 'zh-cn' ),
				array( 'zh', 'zh-tw' ),
				array( 'zh', 'zh-hk' ),
				array( 'zh', 'zh-sg' ),
				array( 'zh', 'zh-mo' ),
				array( 'zh', 'zh-my' ),
				'en',
			) ),
			array( 'zh', LanguageFallbackChainFactory::FALLBACK_SELF, array( 'zh' ) ),
			array( 'zh', LanguageFallbackChainFactory::FALLBACK_VARIANTS, array(
				array( 'zh', 'zh-hans' ),
				array( 'zh', 'zh-hant' ),
				array( 'zh', 'zh-cn' ),
				array( 'zh', 'zh-tw' ),
				array( 'zh', 'zh-hk' ),
				array( 'zh', 'zh-sg' ),
				array( 'zh', 'zh-mo' ),
				array( 'zh', 'zh-my' ),
				array( 'zh', 'zh' ),
			) ),
			array( 'zh', LanguageFallbackChainFactory::FALLBACK_OTHERS, array( 'zh-hans', 'en' ) ),
			array( 'zh', LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_OTHERS,
				array( 'zh', 'zh-hans', 'en' )
			),

			array( 'zh-cn', LanguageFallbackChainFactory::FALLBACK_ALL, array(
				'zh-cn',
				array( 'zh-cn', 'zh-hans' ),
				array( 'zh-cn', 'zh-sg' ),
				array( 'zh-cn', 'zh-my' ),
				array( 'zh-cn', 'zh' ),
				array( 'zh-cn', 'zh-hant' ),
				array( 'zh-cn', 'zh-hk' ),
				array( 'zh-cn', 'zh-mo' ),
				array( 'zh-cn', 'zh-tw' ),
				'en',
			) ),
			array( 'zh-cn', ~LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				array( 'zh-cn', 'zh-hans', 'en' )
			),
			array( 'zh-cn', ~LanguageFallbackChainFactory::FALLBACK_OTHERS, array(
				'zh-cn',
				array( 'zh-cn', 'zh-hans' ),
				array( 'zh-cn', 'zh-sg' ),
				array( 'zh-cn', 'zh-my' ),
				array( 'zh-cn', 'zh' ),
				array( 'zh-cn', 'zh-hant' ),
				array( 'zh-cn', 'zh-hk' ),
				array( 'zh-cn', 'zh-mo' ),
				array( 'zh-cn', 'zh-tw' ),
			) ),

			array( 'ii', LanguageFallbackChainFactory::FALLBACK_ALL, array(
				'ii',
				'zh-cn',
				array( 'zh-cn', 'zh-hans' ),
				array( 'zh-cn', 'zh-sg' ),
				array( 'zh-cn', 'zh-my' ),
				array( 'zh-cn', 'zh' ),
				array( 'zh-cn', 'zh-hant' ),
				array( 'zh-cn', 'zh-hk' ),
				array( 'zh-cn', 'zh-mo' ),
				array( 'zh-cn', 'zh-tw' ),
				'en',
			) ),
			array( 'ii', ~LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				array( 'ii', 'zh-cn', 'zh-hans', 'en' )
			),
			array( 'ii', LanguageFallbackChainFactory::FALLBACK_VARIANTS, array() ),
			array( 'ii', LanguageFallbackChainFactory::FALLBACK_VARIANTS | LanguageFallbackChainFactory::FALLBACK_OTHERS, array(
				'zh-cn',
				array( 'zh-cn', 'zh-hans' ),
				array( 'zh-cn', 'zh-sg' ),
				array( 'zh-cn', 'zh-my' ),
				array( 'zh-cn', 'zh' ),
				array( 'zh-cn', 'zh-hant' ),
				array( 'zh-cn', 'zh-hk' ),
				array( 'zh-cn', 'zh-mo' ),
				array( 'zh-cn', 'zh-tw' ),
				'en',
			) ),
			array( 'ii', LanguageFallbackChainFactory::FALLBACK_OTHERS, array( 'zh-cn', 'zh-hans', 'en' ) ),

			array( 'sr', LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS, array(
				'sr',
				array( 'sr', 'sr-ec' ),
				array( 'sr', 'sr-el' ),
			) ),
		);
	}

	/**
	 * @dataProvider provideNewFromLanguageCodeException
	 * @expectedException MWException
	 */
	public function testNewFromLanguageCodeException( $langCode ) {
		$factory = new LanguageFallbackChainFactory();
		$factory->newFromLanguageCode( $langCode );
	}

	public function provideNewFromLanguageCodeException() {
		return array(
			array( ':' ),
			array( '/' ),
		);
	}

	/**
	 * @group WikibaseLib
	 */
	public function testNewFromContext() {
		$factory = new LanguageFallbackChainFactory();
		$languageFallbackChain = $factory->newFromContext( \RequestContext::getMain() );
		$this->assertTrue( $languageFallbackChain instanceof LanguageFallbackChain );
	}

	/**
	 * @group WikibaseLib
	 * @dataProvider provideTestFromBabel
	 */
	public function testBuildFromBabel( $babel, $expected ) {
		$factory = new LanguageFallbackChainFactory();
		$chain = $factory->buildFromBabel( $babel );
		$this->assertChainEquals( $expected, $chain );
	}

	public function provideTestFromBabel() {
		return array(
			array(
				array(
					'N' => array( 'de-formal' ),
				),
				array(
					'de-formal',
					'de',
					'en',
				),
			),
			array(
				array(
					'N' => array( '/' ),
				),
				array(
				),
			),
			array(
				array(
					'N' => array( ':', 'en' ),
				),
				array(
					'en',
				),
			),
			array(
				array(
					'N' => array( 'unknown' ),
				),
				array(
					'unknown',
					'en',
				),
			),
			array(
				array(
					'N' => array( 'zh-classical' ),
				),
				array(
					'lzh',
					'en',
				),
			),
			array(
				array(
					'N' => array( 'en', 'de-formal' ),
				),
				array(
					'en',
					'de-formal',
					'de',
				),
			),
			array(
				array(
					'N' => array( 'de-formal' ),
					'3' => array( 'en' ),
				),
				array(
					'de-formal',
					'en',
					'de',
				),
			),
			array(
				array(
					'N' => array( 'zh-cn', 'de-formal' ),
					'3' => array( 'en', 'de' ),
				),
				array(
					'zh-cn',
					'de-formal',
					array( 'zh-cn', 'zh-hans' ),
					array( 'zh-cn', 'zh-sg' ),
					array( 'zh-cn', 'zh-my' ),
					array( 'zh-cn', 'zh' ),
					array( 'zh-cn', 'zh-hant' ),
					array( 'zh-cn', 'zh-hk' ),
					array( 'zh-cn', 'zh-mo' ),
					array( 'zh-cn', 'zh-tw' ),
					'en',
					'de',
				),
			),
			array(
				array(
					'N' => array( 'zh-cn', 'zh-hk' ),
					'3' => array( 'en', 'de-formal' ),
				),
				array(
					'zh-cn',
					'zh-hk',
					array( 'zh-cn', 'zh-hans' ),
					array( 'zh-cn', 'zh-sg' ),
					array( 'zh-cn', 'zh-my' ),
					array( 'zh-cn', 'zh' ),
					array( 'zh-cn', 'zh-hant' ),
					array( 'zh-cn', 'zh-mo' ),
					array( 'zh-cn', 'zh-tw' ),
					'en',
					'de-formal',
					'de',
				),
			),
			array(
				array(
					'N' => array( 'en', 'de-formal', 'zh', 'zh-cn' ),
					'4' => array( 'kk-cn' ),
					'2' => array( 'zh-hk', 'kk' ),
				),
				array(
					'en',
					'de-formal',
					'zh',
					'zh-cn',
					array( 'zh', 'zh-hans' ),
					array( 'zh', 'zh-hant' ),
					array( 'zh', 'zh-tw' ),
					array( 'zh', 'zh-hk' ),
					array( 'zh', 'zh-sg' ),
					array( 'zh', 'zh-mo' ),
					array( 'zh', 'zh-my' ),
					'kk-cn',
					array( 'kk-cn', 'kk' ),
					array( 'kk-cn', 'kk-cyrl' ),
					array( 'kk-cn', 'kk-latn' ),
					array( 'kk-cn', 'kk-arab' ),
					array( 'kk-cn', 'kk-kz' ),
					array( 'kk-cn', 'kk-tr' ),
					'de',
				),
			),
		);
	}
}
