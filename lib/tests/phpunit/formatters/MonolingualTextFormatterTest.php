<?php

namespace Wikibase\Formatters\Test;

use DataValues\MonolingualTextValue;
use ValueFormatters\FormatterOptions;
use Wikibase\Formatters\MonolingualTextFormatter;

/**
 * @covers Wikibase\Formatters\MonolingualTextFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MonolingualTextFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider monolingualTextFormatProvider
	 */
	public function testFormat( $value, $options, $pattern ) {
		$formatter = new MonolingualTextFormatter( $options );

		$text = $formatter->format( $value );
		$this->assertRegExp( $pattern, $text );
	}

	public function monolingualTextFormatProvider() {
		$options = new FormatterOptions();

		return array(
			array(
				new MonolingualTextValue( 'de', 'Hallo Welt' ),
				$options,
				'@^Hallo Welt$@'
			),
			array(
				new MonolingualTextValue( 'de', 'Hallo&Welt' ),
				$options,
				'@^Hallo&Welt$@'
			),
		);
	}

}
