<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use Wikibase\Lib\Formatters\EscapingValueFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\EscapingValueFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EscapingValueFormatterTest extends \PHPUnit\Framework\TestCase {

	public function testFormat() {
		$formatter = new EscapingValueFormatter( new StringFormatter( new FormatterOptions() ), 'htmlspecialchars' );
		$value = new StringValue( '3 < 5' );

		$this->assertEquals( '3 &lt; 5', $formatter->format( $value ) );
	}

}
