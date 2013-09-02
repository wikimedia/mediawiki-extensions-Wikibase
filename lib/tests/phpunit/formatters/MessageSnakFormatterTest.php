<?php
namespace Wikibase\Lib\Test;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\MessageSnakFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;

/**
 * @covers Wikibase\Lib\MessageSnakFormatter
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
class MessageSnakFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @covers MessageSnakFormatter::formatSnak()
	 */
	public function testFormatSnak() {
		//TODO: Find a better message for testing, one that actually contains wikitext.
		$msg = wfMessage( 'wikibase-snakview-snaktypeselector-novalue' );
		$snak = new PropertyNoValueSnak( new PropertyId( "P23" ) );

		$formatter = new MessageSnakFormatter( $snak->getType(), $msg, SnakFormatter::FORMAT_WIKI );
		$this->assertEquals( $msg->text(), $formatter->formatSnak( $snak ) );

		$formatter = new MessageSnakFormatter( $snak->getType(), $msg, SnakFormatter::FORMAT_HTML );
		$this->assertEquals( $msg->parse(), $formatter->formatSnak( $snak ) );
	}

	/**
	 * @covers MessageSnakFormatter::getFormat()
	 */
	public function testGetFormat() {
		$msg = wfMessage( 'wikibase-snakview-snaktypeselector-novalue' );
		$formatter = new MessageSnakFormatter( 'any', $msg, 'test' );

		$this->assertEquals( 'test', $formatter->getFormat() );
	}

	/**
	 * @covers MessageSnakFormatter::canFormatSnak()
	 */
	public function testCanFormatSnak() {
		$msg = wfMessage( 'wikibase-snakview-snaktypeselector-novalue' );
		$formatter = new MessageSnakFormatter( 'novalue', $msg, 'test' );

		$snak = new PropertyNoValueSnak( new PropertyId( "P23" ) );
		$this->assertTrue( $formatter->canFormatSnak( $snak ), $snak->getType() );

		$snak = new PropertySomeValueSnak( new PropertyId( "P23" ) );
		$this->assertFalse( $formatter->canFormatSnak( $snak ), $snak->getType() );
	}

}
