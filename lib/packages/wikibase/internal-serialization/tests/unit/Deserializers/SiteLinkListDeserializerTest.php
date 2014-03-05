<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Wikibase\InternalSerialization\Deserializers\SiteLinkListDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\SiteLinkListDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkListDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	public function setUp() {
		$this->deserializer = new SiteLinkListDeserializer();
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_isDeserializerForReturnsFalse( $serialization ) {
		$this->assertFalse(
			$this->deserializer->isDeserializerFor( $serialization ),
			'Should not be able to deserialize invalid serializations'
		);
	}

	public function invalidSerializationProvider() {
		return array(
			array( null ),
			array( 42 ),
			array( 'foo' ),

			array( array( 'foo' ) ),
			array( array( 'foo' => 42 ) ),
			array( array( 'foo' => array() ) ),

			array( array( 'foo' => array( 'bar' => 'baz' ) ) ),
			array( array( 'foo' => array( 'name' => 'baz' ) ) ),
			array( array( 'foo' => array( 'badges' => array() ) ) ),

			// TODO: invalid badges
		);
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$this->deserializer->deserialize( $serialization );
	}

	public function testEmptyListDeserialization() {
		$list = $this->deserializer->deserialize( array() );
		$this->assertInstanceOf( 'Wikibase\DataModel\SiteLinkList', $list );
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testGivenValidSerialization_isDeserializerForReturnsTrue( array $serialization ) {
		$this->assertTrue( $this->deserializer->isDeserializerFor( $serialization ) );
	}

	public function serializationProvider() {
		return array(
			array( array(
			) ),

			array( array(
				'foo' => array(
					'name' => 'bar',
					'badges' => array(),
				),
				'baz' => array(
					'name' => 'bah',
					'badges' => array(),
				)
			) ),

			array( array(
				'foo' => array(
					'name' => 'bar',
					'badges' => array( 'Q42', 'Q1337' ),
				)
			) ),

			array( array(
				'foo' => 'bar',
			) ),

			array( array(
				'foo' => 'bar',
				'baz' => 'bah',
			) ),
		);
	}

	// TODO: test deserialize

}