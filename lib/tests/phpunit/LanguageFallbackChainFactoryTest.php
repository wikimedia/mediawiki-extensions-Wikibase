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

	/**
	 * @group WikibaseLib
	 * @dataProvider providerNewFromLanguage
	 */
	public function testNewFromLanguage( $lang, $mode, $expected ) {
		$factory = new LanguageFallbackChainFactory();
		$chain = $factory->newFromLanguage( \Language::factory( $lang ), $mode )->getFallbackChain();

		$this->assertEquals( count( $expected ), count( $chain ) );
		for ( $i = 0; $i < count( $chain ); $i++ ) {
			if ( is_array( $expected[$i] ) ) {
				$this->assertEquals( $expected[$i][0], $chain[$i]->getLanguage()->getCode() );
				$this->assertEquals( $expected[$i][1], $chain[$i]->getSourceLanguage()->getCode() );
			} else {
				$this->assertEquals( $expected[$i], $chain[$i]->getLanguage()->getCode() );
				$this->assertNull( $chain[$i]->getSourceLanguage() );
			}
		}
	}

	public static function providerNewFromLanguage() {
		return array(
			array( 'en', LanguageFallbackChainFactory::FALLBACK_ALL, array( 'en' ) ),
			array( 'en', LanguageFallbackChainFactory::FALLBACK_VARIANTS, array() ),
			array( 'en', LanguageFallbackChainFactory::FALLBACK_OTHERS, array() ),

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
	 * @group WikibaseLib
	 */
	public function testGetFallbackChainFromContext() {
		$factory = new LanguageFallbackChainFactory();
		$languageFallbackChain = $factory->newFromContext( \RequestContext::getMain() );
		$this->assertTrue( $languageFallbackChain instanceof LanguageFallbackChain );
	}
}
