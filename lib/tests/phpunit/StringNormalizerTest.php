<?php

namespace Wikibase\Test;
use Wikibase\StringNormalizer;
use Wikibase\Utils;

/**
 * Tests for the Wikibase\StringNormalizer class.
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class StringNormalizerTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider providerTrimBadChars
	 */
	public function testTrimBadsChars( $string, $expected ) {
		$normalizer = new StringNormalizer();
		$this->assertEquals( $expected, $normalizer->trimBadChars( $string ) );
	}

	public static function providerTrimBadChars() {
		return array(

			array( // #7: empty
				"",
				""
			),

			array( // #8: just blanks
				" \n ",
				" \n "
			),

			array( // #4: Private Use Area: U+0F818
				"\xef\xa0\x98",
				"\xef\xa0\x98"
			),

			array( // #5: badly truncated cyrillic:
				"\xd0\xb5\xd0",
				"\xd0\xb5",
			),

			array( // #6: badly truncated katakana:
				"\xe3\x82\xa6\xe3\x83",
				"\xe3\x82\xa6"
			),

			array( // #5: badly starting cyrillic:
				"\xb5\xd0\xb5",
				"\xd0\xb5",
			),

			array( // #6: badly starting katakana:
				"\x82\xa6\xe3\x83\xa6",
				"\xe3\x83\xa6"
			),

			// XXX: this should pass, and it does for some versions of PHP/PCRE
			//array( // #7: Latin Extended-D: U+0A7AA
			//	"\xea\x9e\xaa",
			//	"\xea\x9e\xaa",
			//),
		);
	}

	/**
	 * @dataProvider providerTrimWhitespace
	 */
	public function testTrimWhitespace( $string, $expected ) {
		$normalizer = new StringNormalizer();
		$this->assertEquals( $expected, $normalizer->trimWhitespace( $string ) );
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
	 * @dataProvider providerCleanupToNFC
	 */
	public function testCleanupToNFC( $src, $dst, $expected ) {
		$normalizer = new StringNormalizer();

		if ($expected) {
			$this->assertEquals( $dst, $normalizer->cleanupToNFC( $src ), "String '$src' is not the same as the expected '$dst'" );
		}
		else {
			$this->assertFalse( $dst === $normalizer->cleanupToNFC( $src ), "String '$src' (" . urlencode( $src ) . ") is the same as the expected '$dst' (" . urlencode( $dst ) . "). This is unusual, but correct." );
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
	 * @dataProvider providerTrimToNFC
	 */
	public function testTrimToNFC( $src, $dst ) {
		$normalizer = new StringNormalizer();
		$this->assertEquals( $dst, $normalizer->trimToNFC( $src ), "String '$src' is not the same as the expected '$dst'" );
	}

	public static function providerTrimToNFC() {
		return array(
			array( "  \xC3\x85land  øyene  ", 'Åland  øyene' ), // #0
			array( "  A\xCC\x8Aland  øyene  ", 'Åland  øyene' ), // #1
			array( "  \xC3\x85land    øyene  ", 'Åland    øyene' ), // #2
			array( "  A\xCC\x8Aland    øyene  ", 'Åland    øyene' ), // #3


			array( // #4: Private Use Area: U+0F818
				"\xef\xa0\x98",
				"\xef\xa0\x98"
			),

			array( // #5: badly truncated cyrillic:
				"\xd0\xb5\xd0",
				"\xd0\xb5",
			),

			array( // #6: badly truncated katakana:
				"\xe3\x82\xa6\xe3\x83",
				"\xe3\x82\xa6"
			),

			array( // #7: empty
				"",
				""
			),

			array( // #8: just blanks
				" \n ",
				""
			),

			// XXX: this should pass, and it does for some versions of PHP/PCRE
			//array( // #9: Latin Extended-D: U+0A7AA
			//	"\xea\x9e\xaa",
			//	"\xea\x9e\xaa",
			//),
		);
	}
}
