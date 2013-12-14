<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\SnakDeserializer;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testImplementsDeserializationInterface() {
		$deserializer = new SnakDeserializer();

		$this->assertInstanceOf( 'Deserializers\Deserializer', $deserializer );
	}

	public function testGivenNull_isDeserializerForReturnsFalse() {
		$this->assertIsNotSerializerFor( null );
	}

	protected function assertIsNotSerializerFor( $value ) {
		$deserializer = new SnakDeserializer();
		$this->assertFalse( $deserializer->isDeserializerFor( $value ) );
	}

	/**
	 * @dataProvider snakSerializationProvider
	 */
	public function testGivenSnakSerialization_isDeserializerForReturnsTrue( $serialization ) {
		$this->assertIsSerializerFor( $serialization );
	}

	protected function assertIsSerializerFor( $value ) {
		$deserializer = new SnakDeserializer();
		$this->assertTrue( $deserializer->isDeserializerFor( $value ) );
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
				array( 'novalue', 42 )
			),
			array(
				array( 'somevalue', 9001 )
			),
			array(
				array( 'value', 1337, 'string', 'foo' )
			),
		);
	}

}