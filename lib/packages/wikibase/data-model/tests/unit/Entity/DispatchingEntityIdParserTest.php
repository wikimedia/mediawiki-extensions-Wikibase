<?php

namespace Wikibase\DataModel\Tests\Entity;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\DataModel\Entity\DispatchingEntityIdParser
 * @covers Wikibase\DataModel\Entity\EntityIdParser
 *
 * @uses Wikibase\DataModel\Entity\BasicEntityIdParser
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DispatchingEntityIdParserTest extends \PHPUnit_Framework_TestCase {

	private function getBasicParser() {
		return new DispatchingEntityIdParser( BasicEntityIdParser::getBuilders() );
	}

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testCanParseEntityId( EntityId $expected ) {
		$parser = $this->getBasicParser();
		$actual = $parser->parse( $expected->getSerialization() );

		$this->assertEquals( $actual, $expected );
	}

	public function entityIdProvider() {
		return array(
			array( new ItemId( 'q42' ) ),
			array( new ItemId( 'Q1337' ) ),
			array( new PropertyId( 'p1' ) ),
			array( new PropertyId( 'P100000' ) ),
		);
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 * @expectedException \Wikibase\DataModel\Entity\EntityIdParsingException
	 */
	public function testCannotParseInvalidId( $invalidIdSerialization ) {
		$parser = $this->getBasicParser();
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
		);
	}

}
