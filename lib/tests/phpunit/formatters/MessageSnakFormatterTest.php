<?php

namespace Wikibase\Lib\Test;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\Lib\MessageSnakFormatter;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\Lib\MessageSnakFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MessageSnakFormatterTest extends \MediaWikiTestCase {

	public function testFormatSnak() {
		//TODO: Find a better message for testing, one that actually contains wikitext.
		$msg = wfMessage( 'wikibase-snakview-snaktypeselector-novalue' );
		$snak = new PropertyNoValueSnak( new PropertyId( "P23" ) );

		$formatter = new MessageSnakFormatter( $snak->getType(), $msg, SnakFormatter::FORMAT_WIKI );
		$this->assertEquals( $msg->text(), $formatter->formatSnak( $snak ) );

		$formatter = new MessageSnakFormatter( $snak->getType(), $msg, SnakFormatter::FORMAT_HTML );
		$this->assertEquals( $msg->parse(), $formatter->formatSnak( $snak ) );
	}

	public function testGetFormat() {
		$msg = wfMessage( 'wikibase-snakview-snaktypeselector-novalue' );
		$formatter = new MessageSnakFormatter( 'any', $msg, 'test' );

		$this->assertEquals( 'test', $formatter->getFormat() );
	}

	public function testCanFormatSnak() {
		$msg = wfMessage( 'wikibase-snakview-snaktypeselector-novalue' );
		$formatter = new MessageSnakFormatter( 'novalue', $msg, 'test' );

		$snak = new PropertyNoValueSnak( new PropertyId( "P23" ) );
		$this->assertTrue( $formatter->canFormatSnak( $snak ), $snak->getType() );

		$snak = new PropertySomeValueSnak( new PropertyId( "P23" ) );
		$this->assertFalse( $formatter->canFormatSnak( $snak ), $snak->getType() );
	}

}
