<?php

namespace Wikibase\DataModel\Tests\Entity;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\DataModel\Entity\DispatchingEntityIdParser
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class DispatchingEntityIdParserTest extends \PHPUnit_Framework_TestCase {

	private function getBasicParser() {
		return new DispatchingEntityIdParser( BasicEntityIdParser::getBuilders() );
	}

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testCanParseEntityId( $idString, EntityId $expected ) {
		$parser = $this->getBasicParser();
		$actual = $parser->parse( $idString );

		$this->assertEquals( $actual, $expected );
	}

	public function entityIdProvider() {
		return array(
			array( 'q42', new ItemId( 'q42' ) ),
			array( 'Q1337', new ItemId( 'Q1337' ) ),
			array( 'p1', new PropertyId( 'p1' ) ),
			array( 'P100000', new PropertyId( 'P100000' ) ),
		);
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotParseInvalidId( $invalidIdSerialization ) {
		$parser = $this->getBasicParser();

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
		);
	}

	public function testCannotParseWithoutBuilders() {
		$parser = new DispatchingEntityIdParser( array() );

		$this->setExpectedException( 'Wikibase\DataModel\Entity\EntityIdParsingException' );
		$parser->parse( 'Q1' );
	}

}
