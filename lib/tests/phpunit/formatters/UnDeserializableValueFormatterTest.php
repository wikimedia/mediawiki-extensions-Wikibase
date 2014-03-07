<?php
namespace Wikibase\Lib\Test;

use DataValues\UnDeserializableValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\UnDeserializableValueFormatter;

/**
 * @covers Wikibase\Lib\UnDeserializableValueFormatter
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class UnDeserializableValueFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testFormat() {
		$message = $this->getMock( 'Message',
			array( 'text' ),
			array( 'wikibase-undeserializable-value' )
		);

		$message->expects( $this->any() )
			->method( 'text' )
			->will( $this->returnValue( 'bad value' ) );

		$options = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => 'en',
			UnDeserializableValueFormatter::MESSAGE => $message
		) );

		$formatter = new UnDeserializableValueFormatter( $options );
		$value = new UnDeserializableValue(
			'cookie',
			'string',
			'cannot understand!'
		);

		$this->assertEquals( $message->text(), $formatter->format( $value ) );
	}

}
