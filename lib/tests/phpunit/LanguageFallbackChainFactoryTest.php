<?php

namespace Wikibase\Lib\Tests;

use Language;
use MediaWikiTestCase;
use MWException;
use RequestContext;
use User;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;

/**
 * @covers Wikibase\LanguageFallbackChainFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0+
 * @author Liangent < liangent@gmail.com >
 */
class LanguageFallbackChainFactoryTest extends MediaWikiTestCase {

	/**
	 * @param array $expectedItems
	 * @param \Wikibase\LanguageWithConversion[] $chain
	 */
	private function assertChainEquals( array $expectedItems, array $chain ) {
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

	private function setupDisabledVariants( $disabledVariants ) {
		$this->setMwGlobals( [
			'wgDisabledVariants' => $disabledVariants,
			'wgLangObjCacheSize' => 0
		] );
	}

	private function getLanguageFallbackChainFactory() {
		$factory = new LanguageFallbackChainFactory();
		$factory->setGetLanguageFallbacksFor( function( $code ) {
			return $this->getLanguageFallbacksForCallback( $code );
		} );

		return $factory;
	}

	/**
	 * This captures the state of language fallbacks from 2016-08-17.
	 * There's no need for this to be exactly up to date with MediaWiki,
	 * we just need a data base to test with.
	 *
	 * @param string $code
	 *
	 * @return string[]
	 */
	private function getLanguageFallbacksForCallback( $code ) {
		switch ( $code ) {
			case 'en':
				return [];
			case 'de':
				return [ 'en' ];
			case 'de-formal':
				return [ 'de', 'en' ];
			case 'zh':
				return [ 'zh-hans', 'en' ];
			case 'zh-cn':
				return [ 'zh-hans', 'en' ];
			case 'ii':
				return [ 'zh-cn', 'zh-hans', 'en' ];
			case 'lzh':
				return [ 'en' ];
			case 'kk-cn':
				return [ 'kk-arab', 'kk-cyrl', 'en' ];
			case 'zh-hk':
				return [ 'zh-hant', 'zh-hans', 'en' ];
			case 'kk':
				return [ 'kk-cyrl', 'en' ];
			default:
				// Language::getFallbacksFor returns array( 'en' ) if $code is unknown
				return [ 'en' ];
		}
	}

	/**
	 * @dataProvider providerNewFromLanguage
	 */
	public function testNewFromLanguage( $lang, $mode, $expected, $disabledVariants = [] ) {
		$this->setupDisabledVariants( $disabledVariants );
		$factory = $this->getLanguageFallbackChainFactory();
		$chain = $factory->newFromLanguage( Language::factory( $lang ), $mode )->getFallbackChain();
		$this->assertChainEquals( $expected, $chain );
	}

	/**
	 * @dataProvider providerNewFromLanguage
	 */
	public function testNewFromLanguageCode( $lang, $mode, $expected, $disabledVariants = [] ) {
		$this->setupDisabledVariants( $disabledVariants );
		$factory = $this->getLanguageFallbackChainFactory();
		$chain = $factory->newFromLanguageCode( $lang, $mode )->getFallbackChain();
		$this->assertChainEquals( $expected, $chain );
	}

	public function providerNewFromLanguage() {
		return [
			[ 'en', LanguageFallbackChainFactory::FALLBACK_ALL, [ 'en' ] ],
			[ 'en', LanguageFallbackChainFactory::FALLBACK_VARIANTS, [] ],
			[ 'en', LanguageFallbackChainFactory::FALLBACK_OTHERS, [] ],

			[ 'zh-classical', LanguageFallbackChainFactory::FALLBACK_SELF, [ 'lzh' ] ],

			[ 'de-formal', LanguageFallbackChainFactory::FALLBACK_ALL, [ 'de-formal', 'de', 'en' ] ],
			// Repeated to test caching
			[ 'de-formal', LanguageFallbackChainFactory::FALLBACK_ALL, [ 'de-formal', 'de', 'en' ] ],
			[ 'de-formal', LanguageFallbackChainFactory::FALLBACK_VARIANTS, [] ],
			[ 'de-formal', ~LanguageFallbackChainFactory::FALLBACK_SELF, [ 'de', 'en' ] ],

			[ 'zh', LanguageFallbackChainFactory::FALLBACK_ALL, [
				'zh',
				[ 'zh', 'zh-hans' ],
				[ 'zh', 'zh-hant' ],
				[ 'zh', 'zh-cn' ],
				[ 'zh', 'zh-tw' ],
				[ 'zh', 'zh-hk' ],
				[ 'zh', 'zh-sg' ],
				[ 'zh', 'zh-mo' ],
				[ 'zh', 'zh-my' ],
				'en',
			] ],
			[ 'zh', LanguageFallbackChainFactory::FALLBACK_ALL, [
				'zh',
				[ 'zh', 'zh-hans' ],
				[ 'zh', 'zh-hant' ],
				[ 'zh', 'zh-cn' ],
				[ 'zh', 'zh-tw' ],
				[ 'zh', 'zh-hk' ],
				[ 'zh', 'zh-sg' ],
				'en',
			], [ 'zh-mo', 'zh-my' ] ],
			[ 'zh', LanguageFallbackChainFactory::FALLBACK_SELF, [ 'zh' ] ],
			[ 'zh', LanguageFallbackChainFactory::FALLBACK_VARIANTS, [
				[ 'zh', 'zh-hans' ],
				[ 'zh', 'zh-hant' ],
				[ 'zh', 'zh-cn' ],
				[ 'zh', 'zh-tw' ],
				[ 'zh', 'zh-hk' ],
				[ 'zh', 'zh-sg' ],
				[ 'zh', 'zh-mo' ],
				[ 'zh', 'zh-my' ],
				[ 'zh', 'zh' ],
			] ],
			[ 'zh', LanguageFallbackChainFactory::FALLBACK_OTHERS, [ 'zh-hans', 'en' ] ],
			[ 'zh', LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_OTHERS,
				[ 'zh', 'zh-hans', 'en' ]
			],

			[ 'zh-cn', LanguageFallbackChainFactory::FALLBACK_ALL, [
				'zh-cn',
				[ 'zh-cn', 'zh-hans' ],
				[ 'zh-cn', 'zh-sg' ],
				[ 'zh-cn', 'zh-my' ],
				[ 'zh-cn', 'zh' ],
				[ 'zh-cn', 'zh-hant' ],
				[ 'zh-cn', 'zh-hk' ],
				[ 'zh-cn', 'zh-mo' ],
				[ 'zh-cn', 'zh-tw' ],
				'en',
			] ],
			[ 'zh-cn', LanguageFallbackChainFactory::FALLBACK_ALL, [
				'zh-cn',
				[ 'zh-cn', 'zh-sg' ],
				[ 'zh-cn', 'zh' ],
				[ 'zh-cn', 'zh-hant' ],
				[ 'zh-cn', 'zh-hk' ],
				[ 'zh-cn', 'zh-tw' ],
				'zh-hans',
				'en',
			], [ 'zh-mo', 'zh-my', 'zh-hans' ] ],
			[ 'zh-cn', ~LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				[ 'zh-cn', 'zh-hans', 'en' ]
			],
			[ 'zh-cn', ~LanguageFallbackChainFactory::FALLBACK_OTHERS, [
				'zh-cn',
				[ 'zh-cn', 'zh-hans' ],
				[ 'zh-cn', 'zh-sg' ],
				[ 'zh-cn', 'zh-my' ],
				[ 'zh-cn', 'zh' ],
				[ 'zh-cn', 'zh-hant' ],
				[ 'zh-cn', 'zh-hk' ],
				[ 'zh-cn', 'zh-mo' ],
				[ 'zh-cn', 'zh-tw' ],
			] ],

			[ 'ii', LanguageFallbackChainFactory::FALLBACK_ALL, [
				'ii',
				'zh-cn',
				[ 'zh-cn', 'zh-hans' ],
				[ 'zh-cn', 'zh-sg' ],
				[ 'zh-cn', 'zh-my' ],
				[ 'zh-cn', 'zh' ],
				[ 'zh-cn', 'zh-hant' ],
				[ 'zh-cn', 'zh-hk' ],
				[ 'zh-cn', 'zh-mo' ],
				[ 'zh-cn', 'zh-tw' ],
				'en',
			] ],
			[ 'ii', ~LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				[ 'ii', 'zh-cn', 'zh-hans', 'en' ]
			],
			[ 'ii', LanguageFallbackChainFactory::FALLBACK_VARIANTS, [] ],
			[ 'ii', LanguageFallbackChainFactory::FALLBACK_VARIANTS | LanguageFallbackChainFactory::FALLBACK_OTHERS, [
				'zh-cn',
				[ 'zh-cn', 'zh-hans' ],
				[ 'zh-cn', 'zh-sg' ],
				[ 'zh-cn', 'zh-my' ],
				[ 'zh-cn', 'zh' ],
				[ 'zh-cn', 'zh-hant' ],
				[ 'zh-cn', 'zh-hk' ],
				[ 'zh-cn', 'zh-mo' ],
				[ 'zh-cn', 'zh-tw' ],
				'en',
			] ],
			[ 'ii', LanguageFallbackChainFactory::FALLBACK_OTHERS, [ 'zh-cn', 'zh-hans', 'en' ] ],

			[ 'sr', LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS, [
				'sr',
				[ 'sr', 'sr-ec' ],
				[ 'sr', 'sr-el' ],
			] ],
		];
	}

	/**
	 * @dataProvider provideNewFromLanguageCodeException
	 * @expectedException MWException
	 */
	public function testNewFromLanguageCodeException( $langCode ) {
		$factory = $this->getLanguageFallbackChainFactory();
		$factory->newFromLanguageCode( $langCode );
	}

	public function provideNewFromLanguageCodeException() {
		return [
			[ ':' ],
			[ '/' ],
		];
	}

	public function testNewFromContext() {
		$factory = $this->getLanguageFallbackChainFactory();
		$languageFallbackChain = $factory->newFromContext( RequestContext::getMain() );
		$this->assertTrue( $languageFallbackChain instanceof LanguageFallbackChain );
	}

	public function testNewFromContextAndLanguageCode() {
		$factory = $this->getLanguageFallbackChainFactory();
		$languageFallbackChain = $factory->newFromContextAndLanguageCode( RequestContext::getMain(), 'en' );
		$this->assertTrue( $languageFallbackChain instanceof LanguageFallbackChain );
	}

	/**
	 * @dataProvider providerNewFromLanguage
	 */
	public function testNewFromUserAndLanguageCode( $lang, $mode, $expected, $disabledVariants = [] ) {
		if ( $mode !== LanguageFallbackChainFactory::FALLBACK_ALL ) {
			$this->assertTrue( true );
			return;
		}
		$this->setupDisabledVariants( $disabledVariants );
		$factory = $this->getLanguageFallbackChainFactory();
		$anon = new User();
		$chain = $factory->newFromUserAndLanguageCode( $anon, $lang )->getFallbackChain();
		$this->assertChainEquals( $expected, $chain );
	}

	/**
	 * @dataProvider provideTestFromBabel
	 */
	public function testBuildFromBabel( $babel, $expected ) {
		$factory = $this->getLanguageFallbackChainFactory();
		$chain = $factory->buildFromBabel( $babel );
		$this->assertChainEquals( $expected, $chain );
	}

	public function provideTestFromBabel() {
		return [
			[
				[
					'N' => [ 'de-formal' ],
				],
				[
					'de-formal',
					'de',
					'en',
				],
			],
			[
				[
					'N' => [ '/' ],
				],
				[
				],
			],
			[
				[
					'N' => [ ':', 'en' ],
				],
				[
					'en',
				],
			],
			[
				[
					'N' => [ 'unknown' ],
				],
				[
					'unknown',
					'en',
				],
			],
			[
				[
					'N' => [ 'zh-classical' ],
				],
				[
					'lzh',
					'en',
				],
			],
			[
				[
					'N' => [ 'en', 'de-formal' ],
				],
				[
					'en',
					'de-formal',
					'de',
				],
			],
			[
				[
					'N' => [ 'de-formal' ],
					'3' => [ 'en' ],
				],
				[
					'de-formal',
					'en',
					'de',
				],
			],
			[
				[
					'N' => [ 'zh-cn', 'de-formal' ],
					'3' => [ 'en', 'de' ],
				],
				[
					'zh-cn',
					'de-formal',
					[ 'zh-cn', 'zh-hans' ],
					[ 'zh-cn', 'zh-sg' ],
					[ 'zh-cn', 'zh-my' ],
					[ 'zh-cn', 'zh' ],
					[ 'zh-cn', 'zh-hant' ],
					[ 'zh-cn', 'zh-hk' ],
					[ 'zh-cn', 'zh-mo' ],
					[ 'zh-cn', 'zh-tw' ],
					'en',
					'de',
				],
			],
			[
				[
					'N' => [ 'zh-cn', 'zh-hk' ],
					'3' => [ 'en', 'de-formal' ],
				],
				[
					'zh-cn',
					'zh-hk',
					[ 'zh-cn', 'zh-hans' ],
					[ 'zh-cn', 'zh-sg' ],
					[ 'zh-cn', 'zh-my' ],
					[ 'zh-cn', 'zh' ],
					[ 'zh-cn', 'zh-hant' ],
					[ 'zh-cn', 'zh-mo' ],
					[ 'zh-cn', 'zh-tw' ],
					'en',
					'de-formal',
					'de',
				],
			],
			[
				[
					'N' => [ 'en', 'de-formal', 'zh', 'zh-cn' ],
					'4' => [ 'kk-cn' ],
					'2' => [ 'zh-hk', 'kk' ],
				],
				[
					'en',
					'de-formal',
					'zh',
					'zh-cn',
					[ 'zh', 'zh-hans' ],
					[ 'zh', 'zh-hant' ],
					[ 'zh', 'zh-tw' ],
					[ 'zh', 'zh-hk' ],
					[ 'zh', 'zh-sg' ],
					[ 'zh', 'zh-mo' ],
					[ 'zh', 'zh-my' ],
					'kk-cn',
					[ 'kk-cn', 'kk' ],
					[ 'kk-cn', 'kk-cyrl' ],
					[ 'kk-cn', 'kk-latn' ],
					[ 'kk-cn', 'kk-arab' ],
					[ 'kk-cn', 'kk-kz' ],
					[ 'kk-cn', 'kk-tr' ],
					'de',
				],
			],
		];
	}

}
