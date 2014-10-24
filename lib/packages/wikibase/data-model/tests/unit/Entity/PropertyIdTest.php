<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\DataModel\Entity\PropertyId
 * @covers Wikibase\DataModel\Entity\EntityId
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group EntityIdTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyIdTest extends \PHPUnit_Framework_TestCase {

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
			array( 'P2147483648' ),
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
			array( 2147483648 ),
			array( '2147483648' ),
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
			array( '42.1' ),
			array( 42.1 ),
			array( 2147483648.1 ),
		);
	}

}
