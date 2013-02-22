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
	public function testTrimWhitespace( $string, $squashInner, $expected ) {
		$this->assertEquals( $expected, Utils::trimWhitespace( $string, $squashInner ) );
	}

	public static function providerTrimWhitespace() {
		return array(
			array( 'foo bar', false, 'foo bar'), // #0
			array( 'foo bar', true, 'foo bar'), // #1
			array( ' foo  bar ', false, 'foo  bar'), // #2
			array( ' foo  bar ', true, 'foo bar'), // #3
			array( '  foo   bar  ', false, 'foo   bar'), // #4
			array( '  foo   bar  ', true, 'foo bar'), // #5
			array( "foo\tbar", false, 'foo bar'), // #6, both a space and control char
			array( "foo\tbar", true, 'foo bar'), // #7
			array( "foo\nbar", false, 'foo bar'), // #8, both a space and control char
			array( "foo\nbar", true, 'foo bar'), // #9
			array( "foo\rbar", false, 'foo bar'), // #10, both a space and control char
			array( "foo\rbar", true, 'foo bar'), // #11
			array( "\r \t\nfoo\r\t\t\tbar\n\n\n\r\r", false, 'foo    bar'), // #12, both space and control chars
			array( "\r \t\nfoo\r\t\t\tbar\n\n\n\r\r", true, 'foo bar'), // #13
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
	public function testTrimToNFC( $src, $squashInner, $dst ) {
		$this->assertEquals( $dst, Utils::trimToNFC( $src, $squashInner ), "String '$src' is not the same as the expected '$dst'" );
	}

	public static function providerTrimToNFC() {
		return array(
			array( "  \xC3\x85land  øyene  ", false, 'Åland  øyene' ), // #0
			array( "  \xC3\x85land  øyene  ", true, 'Åland øyene' ), // #1
			array( "  A\xCC\x8Aland  øyene  ", false, 'Åland  øyene' ), // #2
			array( "  A\xCC\x8Aland  øyene  ", true, 'Åland øyene' ), // #3
			array( "  \xC3\x85land    øyene  ", false, 'Åland    øyene' ), // #4
			array( "  \xC3\x85land    øyene  ", true, 'Åland øyene' ), // #5
			array( "  A\xCC\x8Aland    øyene  ", false, 'Åland    øyene' ), // #6
			array( "  A\xCC\x8Aland    øyene  ", true, 'Åland øyene' ), // #7
		);
	}

}
