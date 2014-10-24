<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\DataModel\Entity\ItemId
 * @covers Wikibase\DataModel\Entity\EntityId
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group EntityIdTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemIdTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider idSerializationProvider
	 */
	public function testCanConstructId( $idSerialization ) {
		$id = new ItemId( $idSerialization );

		$this->assertEquals(
			strtoupper( $idSerialization ),
			$id->getSerialization()
		);
	}

	public function idSerializationProvider() {
		return array(
			array( 'q1' ),
			array( 'q100' ),
			array( 'q1337' ),
			array( 'q31337' ),
			array( 'Q31337' ),
			array( 'Q42' ),
			array( 'Q2147483648' ),
		);
	}

	/**
	 * @dataProvider invalidIdSerializationProvider
	 */
	public function testCannotConstructWithInvalidSerialization( $invalidSerialization ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new ItemId( $invalidSerialization );
	}

	public function invalidIdSerializationProvider() {
		return array(
			array( 'q' ),
			array( 'p1' ),
			array( 'qq1' ),
			array( '1q' ),
			array( 'q01' ),
			array( 'q 1' ),
			array( ' q1' ),
			array( 'q1 ' ),
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
		$id = ItemId::newFromNumber( $number );
		$this->assertEquals( 'Q' . $number, $id->getSerialization() );
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
		ItemId::newFromNumber( $number );
	}

	public function invalidNumericIdProvider() {
		return array(
			array( '42.1' ),
			array( 42.1 ),
			array( 2147483648.1 ),
		);
	}

}
