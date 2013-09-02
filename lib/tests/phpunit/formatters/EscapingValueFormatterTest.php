<?php
namespace Wikibase\Lib\Test;

use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EscapingValueFormatter;

/**
 * @covers Wikibase\Lib\EscapingValueFormatterTest
 *
 * @since 0.5
 *
 * @ingroup WikibaseLibTest
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EscapingValueFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @covers EscapingValueFormatterTest::format()
	 */
	public function testFormat() {
		$formatter = new EscapingValueFormatter( new StringFormatter( new FormatterOptions() ), 'htmlspecialchars' );
		$value = new StringValue( '3 < 5' );

		$this->assertEquals( '3 &lt; 5', $formatter->format( $value ) );
	}
}
