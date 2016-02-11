<?php

namespace Wikimedia\Purtle\Tests;

use Wikimedia\Purtle\UnicodeEscaper;

/**
 * @covers Wikimedia\Purtle\UnicodeEscaper
 *
 * @group Purtle
 * @group RdfWriter
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class UnicodeEscaperTest extends \PHPUnit_Framework_TestCase {

	public function provideEscapeString() {
		return array(
			'control characters' => array(
				"\x00...\x08\x0B\x0C\x0E...\x19",
				'\u0000...\u0008\u000B\u000C\u000E...\u0019'
			),
			'whitespace' => array(
				" \t\n\r",
				' \t\n\r'
			),
			'non-special ASCII characters' => array(
				'!#$%&\'()*+,-./0...9:;<=>?@A...Z[\\]^_`a...z{|}~',
				'!#$%&\'()*+,-./0...9:;<=>?@A...Z[\\]^_`a...z{|}~'
			),
			'double quote' => array(
				'"',
				'\"'
			),
			'4-digit hex below U+10000' => array(
				"\x7F...\xEF\xBF\xBF",
				'\u007F...\uFFFF'
			),
			'8-digit hex below U+110000' => array(
				"\xF0\x90\x80\x80...\xF4\x8F\xBF\xBF",
				'\U00010000...\U0010FFFF'
			),
			'ignore U+110000 and above' => array(
				"\xF4\x8F\xBF\xC0",
				''
			),
			array(
				"Hello World",
				'Hello World'
			),
			array(
				"Hello\nWorld",
				'Hello\nWorld'
			),
			array(
				"Здравствулте мир",
				'\u0417\u0434\u0440\u0430\u0432\u0441\u0442\u0432\u0443\u043B\u0442\u0435 '
				. '\u043C\u0438\u0440'
			),
			array(
				"여보세요 세계",
				'\uC5EC\uBCF4\uC138\uC694 \uC138\uACC4'
			),
			array(
				"你好世界",
				'\u4F60\u597D\u4E16\u754C'
			),
			array(
				"\xF0\x90\x8C\x80\xF0\x90\x8C\x81\xF0\x90\x8C\x82",
				'\U00010300\U00010301\U00010302'
			)
		);
	}

	/**
	 * @dataProvider provideEscapeString
	 */
	public function testEscapeString( $input, $expected ) {
		$escaper = new UnicodeEscaper();
		$this->assertSame( $expected, $escaper->escapeString( $input ) );
	}

}
