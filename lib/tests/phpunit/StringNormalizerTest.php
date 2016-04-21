<?php

namespace Wikibase\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\StringNormalizer
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class StringNormalizerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider providerTrimBadChars
	 */
	public function testTrimBadsChars( $string, $expected ) {
		$normalizer = new StringNormalizer();
		$this->assertEquals( $expected, $normalizer->trimBadChars( $string ) );
	}

	public function providerTrimBadChars() {
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

	public function providerTrimWhitespace() {
		return array(
			array(
				'foo bar',
				'foo bar'
			),
			array(
				"  foo   bar  \n",
				'foo   bar'
			),
			array(
				"foo\tbar",
				'foo bar'
			),
			array(
				"foo\nbar",
				'foo bar'
			),
			array(
				"foo\rbar",
				'foo bar'
			),
			array(
				"\r \t\nfoo\r\t\t\tbar\n\n\n\r\r",
				'foo bar' ),
			array(
				"\r \t\nfoo\r\t\t\t bar\n\n\n\r\r",
				'foo  bar'
			),
			array(
				html_entity_decode( 'foo&#8204;bar', ENT_QUOTES, 'utf-8' ),
				html_entity_decode( 'foo&#8204;bar', ENT_QUOTES, 'utf-8' )
			),
			array(
				html_entity_decode( 'foo&#8204;&#8204;bar', ENT_QUOTES, 'utf-8' ),
				html_entity_decode( 'foo&#8204;&#8204;bar', ENT_QUOTES, 'utf-8' )
			),
		);
	}

	/**
	 * @dataProvider providerCleanupToNFC
	 */
	public function testCleanupToNFC( $string, $expected ) {
		$normalizer = new StringNormalizer();
		$this->assertSame( $expected, $normalizer->cleanupToNFC( $string ) );
	}

	public function providerCleanupToNFC() {
		return array(
			array( "\xC3\x85land", 'Åland' ),
			array( "A\xCC\x8Aland", 'Åland' ),
			array( "\xE2\x84\xABngstrom (unit)", 'Ångstrom (unit)' ),
		);
	}

	/**
	 * @dataProvider providerTrimToNFC
	 */
	public function testTrimToNFC( $src, $dst ) {
		$normalizer = new StringNormalizer();
		$this->assertEquals( $dst, $normalizer->trimToNFC( $src ), "String '$src' is not the same as the expected '$dst'" );
	}

	public function providerTrimToNFC() {
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
