<?php

namespace Wikibase\Test;
use Wikibase\Utils as Utils;

/**
 * Tests for the Wikibase\Utils class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
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

	public function providerGetLanguageCodes() {
		return array(
			array( 'de' ),
			array( 'en' ),
			array( 'no' ),
			array( 'nn' ),
		);
	}

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerSquashWhitespace
	 */
	public function testSquashWhitespace( $string, $expected ) {
		$this->assertEquals( $expected, Utils::squashWhitespace( $string ) );
	}

	public function providerSquashWhitespace() {
		return array(
			array( 'foo bar', 'foo bar'),
			array( ' foo  bar ', 'foo bar'),
			array( '  foo   bar  ', 'foo bar'),
			array( "foo\tbar", 'foo bar'),
			array( "foo\nbar", 'foo bar'),
			array( "foo\rbar", 'foo bar'),
			array( "\r \t\nfoo\r\t\t\tbar\n\n\n\r\r", 'foo bar'),
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

	public function providerCleanupToNFC() {
		return array(
			array( "\xC3\x85land", 'Åland', true ),
			array( "A\xCC\x8Aland", 'Åland', true ),
			array( "\xE2\x84\xABngstrom (unit)", 'Ångstrom (unit)', false ),
		);
	}

	/**
	 * @group WikibaseUtils
	 * @dataProvider providerSquashToNFC
	 */
	public function testSquashToNFC( $src, $dst ) {
		$this->assertEquals( $dst, Utils::squashToNFC( $src ), "String '$src' is not the same as the expected '$dst'" );
	}

	public function providerSquashToNFC() {
		return array(
			array( "  \xC3\x85land  øyene  ", 'Åland øyene' ),
			array( "  A\xCC\x8Aland  øyene  ", 'Åland øyene' ),
			array( "  \xC3\x85land    øyene  ", 'Åland øyene' ),
			array( "  A\xCC\x8Aland    øyene  ", 'Åland øyene' ),
		);
	}

}