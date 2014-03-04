<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Wikibase\InternalSerialization\Deserializers\SnakDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\SnakDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	public function setUp() {
		$this->deserializer = new SnakDeserializer();
	}

	public function invalidSerializationProvider() {
		return array(
			array( null ),
			array( array() ),
			array( array( 'novalue' ) ),
			array( array( 1337, 'novalue' ) ),
			array( array( 'spam', 1337 ) ),
			array( array( 'novalue', 'daah' ) ),
			array( array( 'novalue', 0 ) ),
			array( array( 'novalue', -1337 ) ),
			array( array( 'novalue', 1337, 'spam' ) ),
			array( array( 'value', 1337 ) ),
			array( array( 'value', 1337, 'data-value-type' ) ),
			array( array( 'value', 1337, 'data-value-type', 'data-value-value', 'spam' ) ),
		);
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_isDeserializerForReturnsFalse( $serialization ) {
		$this->assertFalse( $this->deserializer->isDeserializerFor( $serialization ) );
	}

	public function testGivenValidSerialization_isDeserializerForReturnsTrue() {
		$this->assertCanDeserialize( array(
			'novalue',
			1337,
		) );

		$this->assertCanDeserialize( array(
			'somevalue',
			1,
		) );

		$this->assertCanDeserialize( array(
			'value',
			42,
			'data-value-type',
			'data-value-value'
		) );
	}

	private function assertCanDeserialize( $serialization ) {
		$this->assertTrue( $this->deserializer->isDeserializerFor( $serialization ) );
	}

}