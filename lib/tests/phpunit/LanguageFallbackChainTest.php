<?php

namespace Wikibase\Test;
use Wikibase\LanguageFallbackChain;

/**
 * Tests for the Wikibase\LanguageFallbackChain class.
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
class LanguageFallbackChainTest extends \MediaWikiTestCase {

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerGetFallbackChain
	 */
	public function testNewFromLanguage( $lang, $mode, $expected ) {
		$chain = LanguageFallbackChain::newFromLanguage( \Language::factory( $lang ), $mode )->getFallbackChain();

		$this->assertEquals( count( $chain ), count( $expected ) );
		for ( $i = 0; $i < count( $chain ); $i++ ) {
			if ( is_array( $expected[$i] ) ) {
				$this->assertEquals( $chain[$i]->getLanguage()->getCode(), $expected[$i][0] );
				$this->assertEquals( $chain[$i]->getSourceLanguage()->getCode(), $expected[$i][1] );
			} else {
				$this->assertEquals( $chain[$i]->getLanguage()->getCode(), $expected[$i] );
				$this->assertNull( $chain[$i]->getSourceLanguage() );
			}
		}
	}

	public static function providerGetFallbackChain() {
		return array(
			array( 'en', LanguageFallbackChain::FALLBACK_ALL, array( 'en' ) ),
			array( 'en', LanguageFallbackChain::FALLBACK_VARIANTS, array() ),
			array( 'en', LanguageFallbackChain::FALLBACK_OTHERS, array() ),

			array( 'de-formal', LanguageFallbackChain::FALLBACK_ALL, array( 'de-formal', 'de', 'en' ) ),
			// Repeated to test caching
			array( 'de-formal', LanguageFallbackChain::FALLBACK_ALL, array( 'de-formal', 'de', 'en' ) ),
			array( 'de-formal', LanguageFallbackChain::FALLBACK_VARIANTS, array() ),
			array( 'de-formal', ~LanguageFallbackChain::FALLBACK_SELF, array( 'de', 'en' ) ),

			array( 'zh', LanguageFallbackChain::FALLBACK_ALL, array(
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
			array( 'zh', LanguageFallbackChain::FALLBACK_SELF, array( 'zh' ) ),
			array( 'zh', LanguageFallbackChain::FALLBACK_VARIANTS, array(
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
			array( 'zh', LanguageFallbackChain::FALLBACK_OTHERS, array( 'zh-hans', 'en' ) ),
			array( 'zh', LanguageFallbackChain::FALLBACK_SELF | LanguageFallbackChain::FALLBACK_OTHERS,
				array( 'zh', 'zh-hans', 'en' )
			),

			array( 'zh-cn', LanguageFallbackChain::FALLBACK_ALL, array(
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
			array( 'zh-cn', ~LanguageFallbackChain::FALLBACK_VARIANTS,
				array( 'zh-cn', 'zh-hans', 'en' )
			),
			array( 'zh-cn', ~LanguageFallbackChain::FALLBACK_OTHERS, array(
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

			array( 'ii', LanguageFallbackChain::FALLBACK_ALL, array(
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
			array( 'ii', ~LanguageFallbackChain::FALLBACK_VARIANTS,
				array( 'ii', 'zh-cn', 'zh-hans', 'en' )
			),
			array( 'ii', LanguageFallbackChain::FALLBACK_VARIANTS, array() ),
			array( 'ii', LanguageFallbackChain::FALLBACK_VARIANTS | LanguageFallbackChain::FALLBACK_OTHERS, array(
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
			array( 'ii', LanguageFallbackChain::FALLBACK_OTHERS, array( 'zh-cn', 'zh-hans', 'en' ) ),

			array( 'sr', LanguageFallbackChain::FALLBACK_SELF | LanguageFallbackChain::FALLBACK_VARIANTS, array(
				'sr',
				array( 'sr', 'sr-ec' ),
				array( 'sr', 'sr-el' ),
			) ),
		);
	}

}
