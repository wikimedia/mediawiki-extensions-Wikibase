<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\DataModel\Entity\EntityIdParser
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityIdParserTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider idProvider
	 */
	public function testCanParseEntityId( EntityId $expected ) {
		$actual = $this->newParser()->parse( $expected->getSerialization() );

		$this->assertEquals( $actual, $expected );
	}

	public function idProvider() {
		return array(
			array( new ItemId( 'q42' ) ),
			array( new ItemId( 'Q1337' ) ),
			array( new PropertyId( 'p1' ) ),
			array( new PropertyId( 'P100000' ) ),
		);
	}

	protected function newParser() {
		return new DispatchingEntityIdParser( BasicEntityIdParser::getBuilders() );
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotParserInvalidId( $invalidIdSerialization ) {
		$this->setExpectedException( 'Wikibase\DataModel\Entity\EntityIdParsingException' );
		$this->newParser()->parse( $invalidIdSerialization );
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
