<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\DataModel\Entity\ItemId
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

	public function testNewFromNumber() {
		$id = ItemId::newFromNumber( 42 );
		$this->assertEquals( 'Q42', $id->getSerialization() );
	}

}
