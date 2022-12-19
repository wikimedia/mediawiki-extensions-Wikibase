<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\MonolingualTextValue;
use MediaWikiTestCaseTrait;
use Wikibase\Lib\Formatters\MonolingualTextFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\MonolingualTextFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MonolingualTextFormatterTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiTestCaseTrait;

	/**
	 * @dataProvider monolingualTextFormatProvider
	 */
	public function testFormat( $value, $pattern ) {
		$formatter = new MonolingualTextFormatter();

		$text = $formatter->format( $value );
		$this->assertMatchesRegularExpression( $pattern, $text );
	}

	public function monolingualTextFormatProvider() {
		return [
			[
				new MonolingualTextValue( 'de', 'Hallo Welt' ),
				'@^Hallo Welt$@',
			],
			[
				new MonolingualTextValue( 'de', 'Hallo&Welt' ),
				'@^Hallo&Welt$@',
			],
		];
	}

}
