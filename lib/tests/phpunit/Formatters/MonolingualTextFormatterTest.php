<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\MonolingualTextValue;
use Wikibase\Formatters\MonolingualTextFormatter;

/**
 * @covers Wikibase\Formatters\MonolingualTextFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MonolingualTextFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider monolingualTextFormatProvider
	 */
	public function testFormat( $value, $pattern ) {
		$formatter = new MonolingualTextFormatter();

		$text = $formatter->format( $value );
		$this->assertRegExp( $pattern, $text );
	}

	public function monolingualTextFormatProvider() {
		return [
			[
				new MonolingualTextValue( 'de', 'Hallo Welt' ),
				'@^Hallo Welt$@'
			],
			[
				new MonolingualTextValue( 'de', 'Hallo&Welt' ),
				'@^Hallo&Welt$@'
			],
		];
	}

}
