<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use DataValues\StringValue;
use Wikibase\DataModel\Deserializers\SnakDeserializer;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	protected $dvDeserializer;

	/**
	 * @var SnakDeserializer
	 */
	protected $deserializer;

	public function setUp() {
		$this->dvDeserializer = $this->getMock( 'Deserializers\Deserializer' );
		$this->deserializer = new SnakDeserializer( $this->dvDeserializer );
	}

	public function testImplementsDeserializationInterface() {
		$this->assertInstanceOf( 'Deserializers\Deserializer', $this->deserializer );
	}

	public function testGivenNull_isDeserializerForReturnsFalse() {
		$this->assertIsNotSerializerFor( null );
	}

	protected function assertIsNotSerializerFor( $value ) {
		$this->assertFalse( $this->deserializer->isDeserializerFor( $value ) );
	}

	/**
	 * @dataProvider snakSerializationProvider
	 */
	public function testGivenSnakSerialization_isDeserializerForReturnsTrue( $serialization ) {
		$this->assertIsSerializerFor( $serialization );
	}

	protected function assertIsSerializerFor( $value ) {
		$this->assertTrue( $this->deserializer->isDeserializerFor( $value ) );
	}

	public function testGivenEmptyArray_isDeserializerForReturnsFalse() {
		$this->assertIsNotSerializerFor( array() );
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_isDeserializerForReturnsFalse( $invalidSerialization ) {
		$this->assertIsNotSerializerFor( $invalidSerialization );
	}

	public function invalidSerializationProvider() {
		return array(
			array(
				array( 'foo' )
			),
			array(
				array( 42 )
			),
			array(
				array( 'novalue' )
			),
			array(
				array( 'foo', 42 )
			),
			array(
				array( 'novalue', 'foo' )
			),
			array(
				array( 'value', 1337, 'string' )
			),
			array(
				array( 'value', 1337, 'string', 'foo', 'bar' )
			),
		);
	}

	public function snakSerializationProvider() {
		return array(
			array(
				array( 'novalue', 42 ),
				new PropertyNoValueSnak( 42 )
			),
			array(
				array( 'somevalue', 9001 ),
				new PropertySomeValueSnak( 9001 )
			),
			array(
				array( 'value', 1337, 'string', 'foo' ),
				new PropertyValueSnak( 1337 , new StringValue( 'foo' ) )
			),
		);
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $invalidSerialization ) {
		$this->assertDeserializeThrowsGiven( 'Deserializers\Exceptions\DeserializationException', $invalidSerialization );
	}

	protected function assertDeserializeThrowsGiven( $exceptionClass, $value ) {
		$this->setExpectedException( $exceptionClass );
		$this->deserialize( $value );
	}

	protected function deserialize( $value ) {
		return $this->deserializer->deserialize( $value );
	}

	/**
	 * @dataProvider snakSerializationProvider
	 */
	public function testDeserializeGivenValidSerialization_deserializeReturnsSnak( $serialization, Snak $expectedSnak ) {
		$this->dvDeserializer->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->returnCallback( function( $serialization ) {
				return new StringValue( $serialization['value'] );
			} ) );

		$this->assertEquals(
			$expectedSnak,
			$this->deserialize( $serialization )
		);
	}

}