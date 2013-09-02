<?php
namespace Wikibase\Lib\Test;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\MessageSnakFormatter;
use Wikibase\Lib\SnakFormatterFactory;
use Wikibase\PropertyNoValueSnak;

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

		$formatter = new MessageSnakFormatter( $msg, SnakFormatterFactory::FORMAT_WIKI );
		$this->assertEquals( $msg->text(), $formatter->formatSnak( $snak ) );

		$formatter = new MessageSnakFormatter( $msg, SnakFormatterFactory::FORMAT_HTML );
		$this->assertEquals( $msg->parse(), $formatter->formatSnak( $snak ) );
	}

	/**
	 * @covers MessageSnakFormatter::getFormat()
	 */
	public function testGetFormat() {
		$msg = wfMessage( 'wikibase-snakview-snaktypeselector-novalue' );
		$formatter = new MessageSnakFormatter( $msg, 'test' );

		$this->assertEquals( 'test', $formatter->getFormat() );
	}

}
