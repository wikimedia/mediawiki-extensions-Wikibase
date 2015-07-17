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
 */
class UnicodeEscaperTest extends \PHPUnit_Framework_TestCase {

	public function provideEscapeString() {
		return array(
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

		$this->assertEquals( $expected, $escaper->escapeString( $input ) );
	}

}
