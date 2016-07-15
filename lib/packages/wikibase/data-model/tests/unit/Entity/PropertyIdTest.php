<?php

namespace Wikibase\DataModel\Tests\Entity;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\DataModel\Entity\PropertyId
 * @covers Wikibase\DataModel\Entity\EntityId
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group EntityIdTest
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyIdTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider idSerializationProvider
	 */
	public function testCanConstructId( $idSerialization ) {
		$id = new PropertyId( $idSerialization );

		$this->assertEquals(
			strtoupper( $idSerialization ),
			$id->getSerialization()
		);
	}

	public function idSerializationProvider() {
		return array(
			array( 'p1' ),
			array( 'p100' ),
			array( 'p1337' ),
			array( 'p31337' ),
			array( 'P31337' ),
			array( 'P42' ),
			array( 'P2147483647' ),
		);
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotConstructWithInvalidSerialization( $invalidSerialization ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new PropertyId( $invalidSerialization );
	}

	public function invalidIdSerializationProvider() {
		return array(
			array( "P1\n" ),
			array( 'p' ),
			array( 'q1' ),
			array( 'pp1' ),
			array( '1p' ),
			array( 'p01' ),
			array( 'p 1' ),
			array( ' p1' ),
			array( 'p1 ' ),
			array( '1' ),
			array( ' ' ),
			array( '' ),
			array( '0' ),
			array( 0 ),
			array( 1 ),
			array( 'P2147483648' ),
			array( 'P99999999999' ),
		);
	}

	public function testGetNumericId() {
		$id = new PropertyId( 'P1' );
		$this->assertSame( 1, $id->getNumericId() );
	}

	public function testGetEntityType() {
		$id = new PropertyId( 'P1' );
		$this->assertSame( 'property', $id->getEntityType() );
	}

	public function testSerialize() {
		$id = new PropertyId( 'P1' );
		$this->assertSame( '["property","P1"]', $id->serialize() );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testUnserialize( $json, $expected ) {
		$id = new PropertyId( 'P1' );
		$id->unserialize( $json );
		$this->assertSame( $expected, $id->getSerialization() );
	}

	public function serializationProvider() {
		return array(
			array( '["property","P2"]', 'P2' ),

			// All these cases are kind of an injection vector and allow constructing invalid ids.
			array( '["string","P2"]', 'P2' ),
			array( '["","string"]', 'string' ),
			array( '["",""]', '' ),
			array( '["",2]', 2 ),
			array( '["",null]', null ),
			array( '', null ),
		);
	}

	/**
	 * @dataProvider numericIdProvider
	 */
	public function testNewFromNumber( $number ) {
		$id = PropertyId::newFromNumber( $number );
		$this->assertEquals( 'P' . $number, $id->getSerialization() );
	}

	public function numericIdProvider() {
		return array(
			array( 42 ),
			array( '42' ),
			array( 42.0 ),
			// Check for 32-bit integer overflow on 32-bit PHP systems.
			array( 2147483647 ),
			array( '2147483647' ),
		);
	}

	/**
	 * @dataProvider invalidNumericIdProvider
	 */
	public function testNewFromNumberWithInvalidNumericId( $number ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		PropertyId::newFromNumber( $number );
	}

	public function invalidNumericIdProvider() {
		return array(
			array( 'P1' ),
			array( '42.1' ),
			array( 42.1 ),
			array( 2147483648 ),
			array( '2147483648' ),
		);
	}

}
