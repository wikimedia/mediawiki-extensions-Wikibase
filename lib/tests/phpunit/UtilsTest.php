<?php

namespace Wikibase\Test;
use Wikibase\Utils;

/**
 * Tests for the Wikibase\Utils class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseUtils
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class UtilsTest extends \MediaWikiTestCase {

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerGetLanguageFallbackChain
	 */
	public function testGetLanguageFallbackChain( $lang, $mode, $expected ) {
		$chain = Utils::getLanguageFallbackChain( \Language::factory( $lang ), $mode );

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

	public static function providerGetLanguageFallbackChain() {
		return array(
			array( 'en', Utils::LANGUAGE_FALLBACK_ALL, array( 'en' ) ),
			array( 'en', Utils::LANGUAGE_FALLBACK_VARIANTS, array() ),
			array( 'en', Utils::LANGUAGE_FALLBACK_OTHERS, array() ),

			array( 'zh', Utils::LANGUAGE_FALLBACK_ALL, array(
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
			array( 'zh', Utils::LANGUAGE_FALLBACK_SELF, array( 'zh' ) ),
			array( 'zh', Utils::LANGUAGE_FALLBACK_VARIANTS, array(
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
			array( 'zh', Utils::LANGUAGE_FALLBACK_OTHERS, array( 'zh-hans', 'en' ) ),
			array( 'zh', Utils::LANGUAGE_FALLBACK_SELF | Utils::LANGUAGE_FALLBACK_OTHERS,
				array( 'zh', 'zh-hans', 'en' )
			),

			array( 'zh-cn', Utils::LANGUAGE_FALLBACK_ALL, array(
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
			array( 'zh-cn', ~Utils::LANGUAGE_FALLBACK_VARIANTS,
				array( 'zh-cn', 'zh-hans', 'en' )
			),
			array( 'zh-cn', ~Utils::LANGUAGE_FALLBACK_OTHERS, array(
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

			array( 'ii', Utils::LANGUAGE_FALLBACK_ALL, array(
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
			array( 'ii', ~Utils::LANGUAGE_FALLBACK_VARIANTS,
				array( 'ii', 'zh-cn', 'zh-hans', 'en' )
			),
			array( 'ii', Utils::LANGUAGE_FALLBACK_VARIANTS, array() ),
			array( 'ii', Utils::LANGUAGE_FALLBACK_VARIANTS | Utils::LANGUAGE_FALLBACK_OTHERS, array(
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
			array( 'ii', Utils::LANGUAGE_FALLBACK_OTHERS, array( 'zh-cn', 'zh-hans', 'en' ) ),
		);
	}

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerGetLanguageCodes
	 */
	public function testGetLanguageCodes( $lang ) {
		$result = Utils::getLanguageCodes();
		$this->assertContains(
			$lang,
			$result,
			"The language code {$lang} could not be found in the returned result"
		);
	}

	public static function providerGetLanguageCodes() {
		return array(
			array( 'de' ),
			array( 'en' ),
			array( 'no' ),
			array( 'nn' ),
		);
	}

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerTrimWhitespace
	 */
	public function testTrimWhitespace( $string, $expected ) {
		$this->assertEquals( $expected, Utils::trimWhitespace( $string ) );
	}

	public static function providerTrimWhitespace() {
		return array(
			array( 'foo bar', 'foo bar'), // #0
			array( ' foo  bar ', 'foo  bar'), // #1
			array( '  foo   bar  ', 'foo   bar'), // #2
			array( "foo\tbar", 'foo bar'), // #3, both a space and control char
			array( "foo\nbar", 'foo bar'), // #4, both a space and control char
			array( "foo\rbar", 'foo bar'), // #5, both a space and control char
			array( "\r \t\nfoo\r\t\t\tbar\n\n\n\r\r", 'foo bar'), // #6, both space and control chars
			array( "\r \t\nfoo\r\t\t\t bar\n\n\n\r\r", 'foo  bar'), // #7, both space and control chars
			array( html_entity_decode( "foo&#8204;bar", ENT_QUOTES, "utf-8"), html_entity_decode( "foo&#8204;bar", ENT_QUOTES, "utf-8") ), // #8
			array( html_entity_decode( "foo&#8204;&#8204;bar", ENT_QUOTES, "utf-8"), html_entity_decode( "foo&#8204;&#8204;bar", ENT_QUOTES, "utf-8") ), // #9
		);
	}

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerCleanupToNFC
	 */
	public function testCleanupToNFC( $src, $dst, $expected ) {
		if ($expected) {
			$this->assertEquals( $dst, Utils::cleanupToNFC( $src ), "String '$src' is not the same as the expected '$dst'" );
		}
		else {
			$this->assertFalse( $dst === Utils::cleanupToNFC( $src ), "String '$src' (" . urlencode( $src ) . ") is the same as the expected '$dst' (" . urlencode( $dst ) . "). This is unusual, but correct." );
		}
	}

	public static function providerCleanupToNFC() {
		return array(
			array( "\xC3\x85land", 'Åland', true ),
			array( "A\xCC\x8Aland", 'Åland', true ),
			array( "\xE2\x84\xABngstrom (unit)", 'Ångstrom (unit)', false ),
		);
	}

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerTrimToNFC
	 */
	public function testTrimToNFC( $src, $dst ) {
		$this->assertEquals( $dst, Utils::trimToNFC( $src ), "String '$src' is not the same as the expected '$dst'" );
	}

	public static function providerTrimToNFC() {
		return array(
			array( "  \xC3\x85land  øyene  ", 'Åland  øyene' ), // #0
			array( "  A\xCC\x8Aland  øyene  ", 'Åland  øyene' ), // #1
			array( "  \xC3\x85land    øyene  ", 'Åland    øyene' ), // #2
			array( "  A\xCC\x8Aland    øyene  ", 'Åland    øyene' ), // #3
		);
	}

	public static function provideFetchLanguageName() {
		return array(
			array( // #0
				'en',
				null,
				'English'
			),
			array( // #1
				'de',
				null,
				'Deutsch'
			),
			array( // #2
				'en',
				'de',
				'Englisch'
			),
			array( // #3
				'de',
				'en',
				'German'
			),
		);
	}

	/**
	 * @dataProvider provideFetchLanguageName
	 */
	public function testFetchLanguageName( $lang, $in, $expected ) {
		if ( $in !== null && !defined('CLDR_VERSION') ) {
			$this->markTestSkipped( "CLDR extension required for full language name support" );
		}

		$name = Utils::fetchLanguageName( $lang, $in );
		$this->assertEquals( $expected, $name );
	}

}
