<?php

namespace Wikibase\Lib\Tests;

use MediaWiki\Languages\LanguageFallback;
use MediaWiki\MainConfigNames;
use MediaWikiIntegrationTestCase;
use MWException;
use RequestContext;
use User;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageWithConversion;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Lib\UnionContentLanguages;
use Wikibase\Lib\WikibaseContentLanguages;

/**
 * @covers \Wikibase\Lib\LanguageFallbackChainFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Liangent < liangent@gmail.com >
 */
class LanguageFallbackChainFactoryTest extends MediaWikiIntegrationTestCase {

	public function setUp(): void {
		parent::setUp();

		$this->overrideConfigValue( MainConfigNames::UsePigLatinVariant, false );
	}

	/**
	 * @param array $expectedItems Each element can either be a string language code,
	 * or an array of two strings, the actual and source language code.
	 * @param LanguageWithConversion[] $chain
	 */
	private function assertChainEquals( array $expectedItems, array $chain ) {
		// format both chains into a string for a nice message in case of assertion failure
		$expectedChain = implode( ',', array_map( function ( $expected ) {
			if ( is_array( $expected ) ) {
				return "{$expected[0]}({$expected[1]})";
			} else {
				return $expected;
			}
		}, $expectedItems ) );
		$actualChain = implode( ',', array_map( function ( LanguageWithConversion $actual ) {
			if ( $actual->getSourceLanguageCode() === null ) {
				return $actual->getLanguageCode();
			} else {
				return $actual->getLanguageCode() . '(' . $actual->getSourceLanguageCode() . ')';
			}
		}, $chain ) );
		$this->assertSame( $expectedChain, $actualChain );

		// also compare the chains element by element, in case the string comparison missed a detail
		$this->assertSame( count( $expectedItems ), count( $chain ) );
		foreach ( $expectedItems as $i => $expected ) {
			if ( is_array( $expected ) ) {
				$this->assertSame( $expected[0], $chain[$i]->getLanguageCode() );
				$this->assertSame( $expected[1], $chain[$i]->getSourceLanguageCode() );
			} else {
				$this->assertSame( $expected, $chain[$i]->getLanguageCode() );
				$this->assertNull( $chain[$i]->getSourceLanguageCode() );
			}
		}
	}

	/**
	 * @param string[] $disabledVariants
	 */
	private function setupDisabledVariants( array $disabledVariants ) {
		$this->setMwGlobals( [
			'wgDisabledVariants' => $disabledVariants,
		] );
	}

	private function getLanguageFallbackChainFactory( bool $includeMul = false ) {
		$termsLanguages = WikibaseContentLanguages::getDefaultTermsLanguages();
		if ( $includeMul ) {
			$termsLanguages = new UnionContentLanguages( $termsLanguages, new StaticContentLanguages( [ 'mul' ] ) );
		}

		$languageFallback = $this->createMock( LanguageFallback::class );
		$languageFallback->method( 'getAll' )
			->willReturnCallback( function( $code, $mode = LanguageFallback::MESSAGES ) {
				return $this->getLanguageFallbacksForCallback( $code, $mode );
			} );

		return new LanguageFallbackChainFactory( $termsLanguages, null, null, $languageFallback );
	}

	private function getLanguageFallbacksForCallback( string $code, int $mode ): array {
		$fallbacks = $this->getStrictLanguageFallbacksForCallback( $code );

		if ( $mode === LanguageFallback::MESSAGES && !in_array( 'en', $fallbacks ) ) {
			$fallbacks[] = 'en';
		}

		return $fallbacks;
	}

	/**
	 * A snapshot of language fallbacks, mostly as of 2016-08-17.
	 * There's no need for this to be exactly up to date with MediaWiki,
	 * we just need a data base to test with.
	 *
	 * @param string $code
	 *
	 * @return string[]
	 */
	private function getStrictLanguageFallbacksForCallback( string $code ): array {
		switch ( $code ) {
			case 'en':
				return [];
			case 'de':
				return [];
			case 'de-formal':
				return [ 'de' ];
			case 'ii':
				return [ 'zh-cn', 'zh-hans' ];
			case 'kk':
				return [ 'kk-cyrl' ];
			case 'kk-cn':
				return [ 'kk-arab', 'kk-cyrl' ];
			case 'lzh':
				return []; // actually [ 'zh-hant' ] as of 2022-01-24
			case 'nl-informal': // added 2022-01-24
				return [ 'nl' ];
			case 'sco': // added 2022-01-24
				return [ 'en' ];
			case 'zh':
				return [ 'zh-hans' ];
			case 'zh-cn':
				return [ 'zh-hans' ];
			case 'zh-hk':
				return [ 'zh-hant', 'zh-hans' ];
			default:
				return [];
		}
	}

	/**
	 * @dataProvider providerNewFromLanguage
	 */
	public function testNewFromLanguage(
		$languageCode,
		array $expected,
		array $disabledVariants = [],
		bool $includeMul = false
	) {
		$this->setupDisabledVariants( $disabledVariants );
		$factory = $this->getLanguageFallbackChainFactory( $includeMul );
		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( $languageCode );
		$chain = $factory->newFromLanguage( $lang )->getFallbackChain();
		$this->assertChainEquals( $expected, $chain );
	}

	/**
	 * @dataProvider providerNewFromLanguage
	 */
	public function testNewFromLanguageCode(
		$languageCode,
		array $expected,
		array $disabledVariants = [],
		bool $includeMul = false
	) {
		$this->setupDisabledVariants( $disabledVariants );
		$factory = $this->getLanguageFallbackChainFactory( $includeMul );
		$chain = $factory->newFromLanguageCode( $languageCode )->getFallbackChain();
		$this->assertChainEquals( $expected, $chain );
	}

	public function providerNewFromLanguage() {
		return [
			[
				'languageCode' => 'en',
				'expected' => [ 'en' ],
			],

			[
				'languageCode' => 'zh-classical',
				'expected' => [ 'lzh', 'en' ],
			],

			[
				'languageCode' => 'de-formal',
				'expected' => [ 'de-formal', 'de', 'en' ],
			],
			// Repeated to test caching
			[
				'languageCode' => 'de-formal',
				'expected' => [ 'de-formal', 'de', 'en' ],
			],

			[
				'languageCode' => 'zh',
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
				],
			],
			[
				'languageCode' => 'zh',
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
				'disabledVariants' => [ 'zh-mo', 'zh-my' ],
			],
			[
				'languageCode' => 'zh-cn',
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
				],
			],
			[
				'languageCode' => 'zh-cn',
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
				'disabledVariants' => [ 'zh-mo', 'zh-my', 'zh-hans' ],
			],

			[
				'languageCode' => 'ii',
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
				],
			],

			[
				'languageCode' => 'kk',
				'expected' => [
					'kk',
					[ 'kk', 'kk-cyrl' ],
					[ 'kk', 'kk-latn' ],
					[ 'kk', 'kk-arab' ],
					[ 'kk', 'kk-kz' ],
					[ 'kk', 'kk-tr' ],
					[ 'kk', 'kk-cn' ],
					'en',
				],
			],
			[
				'languageCode' => '⧼Lang⧽',
				'expected' => [ 'en' ],
			],

			'implicit fallback to mul, en' => [
				'languageCode' => 'de',
				'expected' => [ 'de', 'mul', 'en' ],
				'disabledVariants' => [],
				'includeMul' => true,
			],

			'explicit fallback to en before implicit fallback to mul' => [
				'languageCode' => 'sco',
				'expected' => [ 'sco', 'en', 'mul' ],
				'disabledVariants' => [],
				'includeMul' => true,
			],
		];
	}

	/**
	 * @dataProvider provideNewFromLanguageCodeException
	 */
	public function testNewFromLanguageCodeException( $languageCode ) {
		$factory = $this->getLanguageFallbackChainFactory();
		$this->expectException( MWException::class );
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
		$this->assertTrue( $languageFallbackChain instanceof TermLanguageFallbackChain );
	}

	public function testNewFromContextAndLanguageCode() {
		$factory = $this->getLanguageFallbackChainFactory();
		$languageFallbackChain = $factory->newFromContextAndLanguageCode( RequestContext::getMain(), 'en' );
		$this->assertTrue( $languageFallbackChain instanceof TermLanguageFallbackChain );
	}

	/**
	 * @dataProvider providerNewFromLanguage
	 */
	public function testNewFromUserAndLanguageCode(
		$languageCode,
		array $expected,
		array $disabledVariants = [],
		bool $includeMul = false
	) {
		$this->setupDisabledVariants( $disabledVariants );
		$factory = $this->getLanguageFallbackChainFactory( $includeMul );
		$anon = new User();
		$chain = $factory->newFromUserAndLanguageCode( $anon, $languageCode )->getFallbackChain();
		$this->assertChainEquals( $expected, $chain );
	}

	/**
	 * @dataProvider provideTestFromBabel
	 */
	public function testBuildFromBabel(
		array $babel,
		array $expected,
		array $disabledVariants = [],
		bool $includeMul = false
	) {
		$this->setupDisabledVariants( $disabledVariants );
		$factory = $this->getLanguageFallbackChainFactory( $includeMul );
		$chain = $factory->buildFromBabel( $babel );
		if ( !$includeMul ) {
			// buildFromBabel always returns mul (it’s usually filtered out by the TermLanguageFallbackChain constructor)
			$chain = array_values( array_filter( $chain, static function ( LanguageWithConversion $language ) {
				return $language->getLanguageCode() !== 'mul';
			} ) );
		}
		$this->assertChainEquals( $expected, $chain );
	}

	public function provideTestFromBabel() {
		return [
			[
				'babel' => [ 'N' => [ 'de-formal' ] ],
				'expected' => [ 'de-formal', 'de', 'en' ],
			],
			[
				'babel' => [ 'N' => [ 'de-formal' ] ],
				'expected' => [ 'de-formal', 'de', 'mul', 'en' ],
				'disabledVariants' => [],
				'includeMul' => true,
			],
			[
				'babel' => [ 'N' => [ ':', 'en' ] ],
				'expected' => [ 'en' ],
			],
			[
				'babel' => [ 'N' => [ 'unknown' ] ],
				'expected' => [ 'unknown', 'en' ],
			],
			[
				'babel' => [ 'N' => [ 'zh-classical' ] ],
				'expected' => [ 'lzh', 'en' ],
			],
			[
				'babel' => [ 'N' => [ 'en', 'de-formal' ] ],
				'expected' => [ 'en', 'de-formal', 'de' ],
			],
			[
				'babel' => [ 'N' => [ 'de-formal' ], '3' => [ 'en' ] ],
				'expected' => [ 'de-formal', 'en', 'de' ],
			],
			[
				'babel' => [ 'N' => [ 'zh' ] ],
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
				],
			],
			[
				'babel' => [ 'N' => [ 'zh' ] ],
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
				'disabledVariants' => [ 'zh-mo', 'zh-my' ],
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
				],
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
				],
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
				],
			],
			'T299904' => [
				'babel' => [ 'N' => [ 'de-formal' ], '4' => [ 'nl-informal' ] ],
				'expected' => [
					'de-formal',
					'nl-informal',
					'de',
					'nl',
					'en',
				],
			],
			'explicit sco-4/en fallback > implicit de-N/mul fallback' => [
				'babel' => [ 'N' => [ 'de' ], '4' => [ 'sco' ] ],
				'expected' => [
					'de',
					'sco',
					'en',
					'mul',
				],
				'disabledVariants' => [],
				'includeMul' => true,
			],
		];
	}

}
