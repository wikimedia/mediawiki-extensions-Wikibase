<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Wikibase\InternalSerialization\Deserializers\SiteLinkListDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\SiteLinkDeserializer
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

	public function invalidSerializationProvider() {
		return array(
			array( null ),
		);
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

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$this->deserializer->deserialize( $serialization );
	}

}