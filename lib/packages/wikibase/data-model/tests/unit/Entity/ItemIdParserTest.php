<?php

namespace Wikibase\DataModel\Tests\Entity;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;

/**
 * @covers Wikibase\DataModel\Entity\ItemIdParser
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class ItemIdParserTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testCanParseEntityId( $idString, ItemId $expected ) {
		$parser = new ItemIdParser();
		$actual = $parser->parse( $idString );

		$this->assertEquals( $actual, $expected );
	}

	public function entityIdProvider() {
		return array(
			array( 'q42', new ItemId( 'Q42' ) ),
			array( 'Q1337', new ItemId( 'Q1337' ) ),
		);
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotParseInvalidId( $invalidIdSerialization ) {
		$parser = new ItemIdParser();

		$this->setExpectedException( 'Wikibase\DataModel\Entity\EntityIdParsingException' );
		$parser->parse( $invalidIdSerialization );
	}

	public function invalidIdSerializationProvider() {
		return array(
			array( 'FOO' ),
			array( null ),
			array( 42 ),
			array( array() ),
			array( '' ),
			array( 'q0' ),
			array( '1p' ),
			array( 'p1' ),
			array( 'P100000' ),
		);
	}

}
