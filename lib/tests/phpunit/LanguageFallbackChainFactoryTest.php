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
		$this->assertSame( count( $expectedItems ), count( $chain ) );

		foreach ( $expectedItems as $i => $expected ) {
			if ( is_array( $expected ) ) {
				$this->assertSame( $expected[0], $chain[$i]->getLanguage()->getCode() );
				$this->assertSame( $expected[1], $chain[$i]->getSourceLanguage()->getCode() );
			} else {
				$this->assertSame( $expected, $chain[$i]->getLanguage()->getCode() );
				$this->assertNull( $chain[$i]->getSourceLanguage() );
			}
		}
	}

	/**
	 * @param string[] $disabledVariants
	 */
	private function setupDisabledVariants( array $disabledVariants ) {
		$this->setMwGlobals( [
			'wgDisabledVariants' => $disabledVariants,
			'wgLangObjCacheSize' => 0,
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
				// Language::getFallbacksFor returns [ 'en' ] if $code is unknown
				return [ 'en' ];
		}
	}

	/**
	 * @dataProvider providerNewFromLanguage
	 */
	public function testNewFromLanguage(
		$languageCode,
		$mode,
		array $expected,
		array $disabledVariants = []
	) {
		$this->setupDisabledVariants( $disabledVariants );
		$factory = $this->getLanguageFallbackChainFactory();
		$chain = $factory->newFromLanguage( Language::factory( $languageCode ), $mode )->getFallbackChain();
		$this->assertChainEquals( $expected, $chain );
	}

	/**
	 * @dataProvider providerNewFromLanguage
	 */
	public function testNewFromLanguageCode(
		$languageCode,
		$mode,
		array $expected,
		array $disabledVariants = []
	) {
		$this->setupDisabledVariants( $disabledVariants );
		$factory = $this->getLanguageFallbackChainFactory();
		$chain = $factory->newFromLanguageCode( $languageCode, $mode )->getFallbackChain();
		$this->assertChainEquals( $expected, $chain );
	}

	public function providerNewFromLanguage() {
		return [
			[
				'languageCode' => 'en',
				'mode' => LanguageFallbackChainFactory::FALLBACK_ALL,
				'expected' => [ 'en' ]
			],
			[
				'languageCode' => 'en',
				'mode' => LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				'expected' => []
			],
			[
				'languageCode' => 'en',
				'mode' => LanguageFallbackChainFactory::FALLBACK_OTHERS,
				'expected' => []
			],

			[
				'languageCode' => 'zh-classical',
				'mode' => LanguageFallbackChainFactory::FALLBACK_SELF,
				'expected' => [ 'lzh' ]
			],

			[
				'languageCode' => 'de-formal',
				'mode' => LanguageFallbackChainFactory::FALLBACK_ALL,
				'expected' => [ 'de-formal', 'de', 'en' ]
			],
			// Repeated to test caching
			[
				'languageCode' => 'de-formal',
				'mode' => LanguageFallbackChainFactory::FALLBACK_ALL,
				'expected' => [ 'de-formal', 'de', 'en' ]
			],
			[
				'languageCode' => 'de-formal',
				'mode' => LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				'expected' => []
			],
			[
				'languageCode' => 'de-formal',
				'mode' => ~LanguageFallbackChainFactory::FALLBACK_SELF,
				'expected' => [ 'de', 'en' ]
			],

			[
				'languageCode' => 'zh',
				'mode' => LanguageFallbackChainFactory::FALLBACK_ALL,
				'expected' => [
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
				]
			],
			[
				'languageCode' => 'zh',
				'mode' => LanguageFallbackChainFactory::FALLBACK_ALL,
				'expected' => [
					'zh',
					[ 'zh', 'zh-hans' ],
					[ 'zh', 'zh-hant' ],
					[ 'zh', 'zh-cn' ],
					[ 'zh', 'zh-tw' ],
					[ 'zh', 'zh-hk' ],
					[ 'zh', 'zh-sg' ],
					'en',
				],
				'disabledVariants' => [ 'zh-mo', 'zh-my' ]
			],
			[
				'languageCode' => 'zh',
				'mode' => LanguageFallbackChainFactory::FALLBACK_SELF,
				'expected' => [ 'zh' ]
			],
			[
				'languageCode' => 'zh',
				'mode' => LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				'expected' => [
					[ 'zh', 'zh-hans' ],
					[ 'zh', 'zh-hant' ],
					[ 'zh', 'zh-cn' ],
					[ 'zh', 'zh-tw' ],
					[ 'zh', 'zh-hk' ],
					[ 'zh', 'zh-sg' ],
					[ 'zh', 'zh-mo' ],
					[ 'zh', 'zh-my' ],
				]
			],
			[
				'languageCode' => 'zh',
				'mode' => LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				'expected' => [
					'zh', // This should be the only difference to the test case above
					[ 'zh', 'zh-hans' ],
					[ 'zh', 'zh-hant' ],
					[ 'zh', 'zh-cn' ],
					[ 'zh', 'zh-tw' ],
					[ 'zh', 'zh-hk' ],
					[ 'zh', 'zh-sg' ],
					[ 'zh', 'zh-mo' ],
					[ 'zh', 'zh-my' ],
				]
			],
			[
				'languageCode' => 'zh',
				'mode' => LanguageFallbackChainFactory::FALLBACK_OTHERS,
				'expected' => [ 'zh-hans', 'en' ]
			],
			[
				'languageCode' => 'zh',
				'mode' => LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_OTHERS,
				'expected' => [ 'zh', 'zh-hans', 'en' ]
			],
			[
				'languageCode' => 'zh-cn',
				'mode' => LanguageFallbackChainFactory::FALLBACK_ALL,
				'expected' => [
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
				]
			],
			[
				'languageCode' => 'zh-cn',
				'mode' => LanguageFallbackChainFactory::FALLBACK_ALL,
				'expected' => [
					'zh-cn',
					[ 'zh-cn', 'zh-sg' ],
					[ 'zh-cn', 'zh' ],
					[ 'zh-cn', 'zh-hant' ],
					[ 'zh-cn', 'zh-hk' ],
					[ 'zh-cn', 'zh-tw' ],
					'zh-hans',
					'en',
				],
				'disabledVariants' => [ 'zh-mo', 'zh-my', 'zh-hans' ]
			],
			[
				'languageCode' => 'zh-cn',
				'mode' => ~LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				'expected' => [ 'zh-cn', 'zh-hans', 'en' ]
			],
			[
				'languageCode' => 'zh-cn',
				'mode' => ~LanguageFallbackChainFactory::FALLBACK_OTHERS,
				'expected' => [
					'zh-cn',
					[ 'zh-cn', 'zh-hans' ],
					[ 'zh-cn', 'zh-sg' ],
					[ 'zh-cn', 'zh-my' ],
					[ 'zh-cn', 'zh' ],
					[ 'zh-cn', 'zh-hant' ],
					[ 'zh-cn', 'zh-hk' ],
					[ 'zh-cn', 'zh-mo' ],
					[ 'zh-cn', 'zh-tw' ],
				],
			],

			[
				'languageCode' => 'ii',
				'mode' => LanguageFallbackChainFactory::FALLBACK_ALL,
				'expected' => [
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
				]
			],
			[
				'languageCode' => 'ii',
				'mode' => ~LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				'expected' => [ 'ii', 'zh-cn', 'zh-hans', 'en' ]
			],
			[
				'languageCode' => 'ii',
				'mode' => LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				'expected' => []
			],
			[
				'languageCode' => 'ii',
				'mode' => LanguageFallbackChainFactory::FALLBACK_VARIANTS | LanguageFallbackChainFactory::FALLBACK_OTHERS,
				'expected' => [
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
				]
			],
			[
				'languageCode' => 'ii',
				'mode' => LanguageFallbackChainFactory::FALLBACK_OTHERS,
				'expected' => [ 'zh-cn', 'zh-hans', 'en' ]
			],

			[
				'languageCode' => 'kk',
				'mode' => LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS,
				'expected' => [
					'kk',
					[ 'kk', 'kk-cyrl' ],
					[ 'kk', 'kk-latn' ],
					[ 'kk', 'kk-arab' ],
					[ 'kk', 'kk-kz' ],
					[ 'kk', 'kk-tr' ],
					[ 'kk', 'kk-cn' ],
				]
			],
		];
	}

	/**
	 * @dataProvider provideNewFromLanguageCodeException
	 * @expectedException MWException
	 */
	public function testNewFromLanguageCodeException( $languageCode ) {
		$factory = $this->getLanguageFallbackChainFactory();
		$factory->newFromLanguageCode( $languageCode );
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
	public function testNewFromUserAndLanguageCode(
		$languageCode,
		$mode,
		array $expected,
		array $disabledVariants = []
	) {
		if ( $mode !== LanguageFallbackChainFactory::FALLBACK_ALL ) {
			$this->assertTrue( true );
			return;
		}
		$this->setupDisabledVariants( $disabledVariants );
		$factory = $this->getLanguageFallbackChainFactory();
		$anon = new User();
		$chain = $factory->newFromUserAndLanguageCode( $anon, $languageCode )->getFallbackChain();
		$this->assertChainEquals( $expected, $chain );
	}

	/**
	 * @dataProvider provideTestFromBabel
	 */
	public function testBuildFromBabel( array $babel, array $expected ) {
		$factory = $this->getLanguageFallbackChainFactory();
		$chain = $factory->buildFromBabel( $babel );
		$this->assertChainEquals( $expected, $chain );
	}

	public function provideTestFromBabel() {
		return [
			[
				'babel' => [ 'N' => [ 'de-formal' ] ],
				'expected' => [ 'de-formal', 'de', 'en' ]
			],
			[
				'babel' => [ 'N' => [ '/' ] ],
				'expected' => []
			],
			[
				'babel' => [ 'N' => [ ':', 'en' ] ],
				'expected' => [ 'en' ]
			],
			[
				'babel' => [ 'N' => [ 'unknown' ] ],
				'expected' => [ 'unknown', 'en' ]
			],
			[
				'babel' => [ 'N' => [ 'zh-classical' ] ],
				'expected' => [ 'lzh', 'en' ]
			],
			[
				'babel' => [ 'N' => [ 'en', 'de-formal' ] ],
				'expected' => [ 'en', 'de-formal', 'de' ]
			],
			[
				'babel' => [ 'N' => [ 'de-formal' ], '3' => [ 'en' ] ],
				'expected' => [ 'de-formal', 'en', 'de' ]
			],
			[
				'babel' => [ 'N' => [ 'zh-cn', 'de-formal' ], '3' => [ 'en', 'de' ] ],
				'expected' => [
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
				]
			],
			[
				'babel' => [ 'N' => [ 'zh-cn', 'zh-hk' ], '3' => [ 'en', 'de-formal' ] ],
				'expected' => [
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
				]
			],
			[
				'babel' => [
					'N' => [ 'en', 'de-formal', 'zh', 'zh-cn' ],
					'4' => [ 'kk-cn' ],
					'2' => [ 'zh-hk', 'kk' ],
				],
				'expected' => [
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
				]
			],
		];
	}

}
