<?php

namespace Wikibase\Lib\Tests;

use Wikibase\Lib\StringNormalizer;

/**
 * @covers \Wikibase\Lib\StringNormalizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class StringNormalizerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider providerTrimBadChars
	 */
	public function testTrimBadsChars( $string, $expected ) {
		$normalizer = new StringNormalizer();
		$this->assertEquals( $expected, $normalizer->trimBadChars( $string ) );
	}

	public function providerTrimBadChars() {
		return [
			[ // #7: empty
				"",
				"",
			],

			[ // #8: just blanks
				" \n ",
				" \n ",
			],

			[ // #4: Private Use Area: U+0F818
				"\xef\xa0\x98",
				"\xef\xa0\x98",
			],

			[ // #5: badly truncated cyrillic:
				"\xd0\xb5\xd0",
				"\xd0\xb5",
			],

			[ // #6: badly truncated katakana:
				"\xe3\x82\xa6\xe3\x83",
				"\xe3\x82\xa6",
			],

			[ // #5: badly starting cyrillic:
				"\xb5\xd0\xb5",
				"\xd0\xb5",
			],

			[ // #6: badly starting katakana:
				"\x82\xa6\xe3\x83\xa6",
				"\xe3\x83\xa6",
			],

			// XXX: this should pass, and it does for some versions of PHP/PCRE
			//array( // #7: Latin Extended-D: U+0A7AA
			//	"\xea\x9e\xaa",
			//	"\xea\x9e\xaa",
			//),
		];
	}

	/**
	 * @dataProvider providerTrimWhitespace
	 */
	public function testTrimWhitespace( $string, $expected ) {
		$normalizer = new StringNormalizer();
		$this->assertEquals( $expected, $normalizer->trimWhitespace( $string ) );
	}

	public function providerTrimWhitespace() {
		return [
			[
				'foo bar',
				'foo bar',
			],
			[
				"  foo   bar  \n",
				'foo   bar',
			],
			[
				"foo\tbar",
				'foo bar',
			],
			[
				"foo\nbar",
				'foo bar',
			],
			[
				"foo\rbar",
				'foo bar',
			],
			[
				"\r \t\nfoo\r\t\t\tbar\n\n\n\r\r",
				'foo bar' ],
			[
				"\r \t\nfoo\r\t\t\t bar\n\n\n\r\r",
				'foo  bar',
			],
			[
				html_entity_decode( 'foo&#8204;bar', ENT_QUOTES, 'utf-8' ),
				html_entity_decode( 'foo&#8204;bar', ENT_QUOTES, 'utf-8' ),
			],
			[
				html_entity_decode( 'foo&#8204;&#8204;bar', ENT_QUOTES, 'utf-8' ),
				html_entity_decode( 'foo&#8204;&#8204;bar', ENT_QUOTES, 'utf-8' ),
			],
		];
	}

	/**
	 * @dataProvider providerCleanupToNFC
	 */
	public function testCleanupToNFC( $string, $expected ) {
		$normalizer = new StringNormalizer();
		$this->assertSame( $expected, $normalizer->cleanupToNFC( $string ) );
	}

	public function providerCleanupToNFC() {
		return [
			[ "\xC3\x85land", 'Åland' ],
			[ "A\xCC\x8Aland", 'Åland' ],
			[ "\xE2\x84\xABngstrom (unit)", 'Ångstrom (unit)' ],
		];
	}

	/**
	 * @dataProvider providerTrimToNFC
	 */
	public function testTrimToNFC( $src, $dst ) {
		$normalizer = new StringNormalizer();
		$this->assertEquals( $dst, $normalizer->trimToNFC( $src ), "String '$src' is not the same as the expected '$dst'" );
	}

	public function providerTrimToNFC() {
		return [
			[ "  \xC3\x85land  øyene  ", 'Åland  øyene' ], // #0
			[ "  A\xCC\x8Aland  øyene  ", 'Åland  øyene' ], // #1
			[ "  \xC3\x85land    øyene  ", 'Åland    øyene' ], // #2
			[ "  A\xCC\x8Aland    øyene  ", 'Åland    øyene' ], // #3

			[ // #4: Private Use Area: U+0F818
				"\xef\xa0\x98",
				"\xef\xa0\x98",
			],

			[ // #5: badly truncated cyrillic:
				"\xd0\xb5\xd0",
				"\xd0\xb5",
			],

			[ // #6: badly truncated katakana:
				"\xe3\x82\xa6\xe3\x83",
				"\xe3\x82\xa6",
			],

			[ // #7: empty
				"",
				"",
			],

			[ // #8: just blanks
				" \n ",
				"",
			],

			// XXX: this should pass, and it does for some versions of PHP/PCRE
			//array( // #9: Latin Extended-D: U+0A7AA
			//	"\xea\x9e\xaa",
			//	"\xea\x9e\xaa",
			//),
		];
	}

}
