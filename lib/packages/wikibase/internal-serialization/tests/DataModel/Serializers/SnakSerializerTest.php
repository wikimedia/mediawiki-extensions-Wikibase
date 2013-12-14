<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Serializers\SnakSerializer;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakSerializerTest extends \PHPUnit_Framework_TestCase {

	public function testImplementsSerializationInterface() {
		$serializer = new SnakSerializer();

		$this->assertInstanceOf( 'Serializers\Serializer', $serializer );
	}

	public function testGivenSnak_isSerializerForReturnsTrue() {
		$snak = $this->getMock( 'Wikibase\DataModel\Snak\Snak' );

		$serializer = new SnakSerializer();

		$this->assertTrue( $serializer->isSerializerFor( $snak ) );
	}

	/**
	 * @dataProvider nonSnakProvider
	 */
	public function testGivenNonSnak_isSerializerForReturnsFalse( $nonSnak ) {
		$serializer = new SnakSerializer();

		$this->assertFalse( $serializer->isSerializerFor( $nonSnak ) );
	}

	/**
	 * @dataProvider nonSnakProvider
	 */
	public function testGivenNonSnak_throwsUnsupportedObjectException( $nonSnak ) {
		$serializer = new SnakSerializer();

		$this->setExpectedException( 'Serializers\Exceptions\UnsupportedObjectException' );
		$serializer->serialize( $nonSnak );
	}

	public function testGivenSnak_returnsToArrayResult() {
		$serialization = array(
			'I am',
			'in your' => 'code',
			'~=[,,_,,]:3'
		);

		$snak = $this->getMock( 'Wikibase\DataModel\Snak\Snak' );

		$snak->expects( $this->once() )
			->method( 'toArray' )
			->will( $this->returnValue( $serialization ) );

		$serializer = new SnakSerializer();

		$returnedValue = $serializer->serialize( $snak );

		$this->assertEquals( $serialization, $returnedValue );
	}

	public function nonSnakProvider() {
		return array(
			array( null ),
			array( 42 ),
			array( 'foo' ),
			array( true ),
			array( array() ),
			array( (object)array() ),
		);
	}

}
