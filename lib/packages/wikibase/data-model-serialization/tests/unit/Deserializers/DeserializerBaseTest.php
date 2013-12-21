<?php

namespace Tests\Wikibase\DataModel\Unserializers;

use Deserializers\Deserializer;

/**
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
abstract class DeserializerBaseTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return Deserializer
	 */
	public abstract function buildDeserializer();

	public function testImplementsDeserializerInterface() {
		$this->assertInstanceOf( 'Deserializers\Deserializer', $this->buildDeserializer() );
	}

	/**
	 * @dataProvider deserializableProvider
	 */
	public function testIsDeserializerForReturnsTrue( $serializable ) {
		$this->assertTrue( $this->buildDeserializer()->isDeserializerFor( $serializable ) );
	}

	/**
	 * @return mixed[] things that are deserialized by the deserializer
	 */
	public abstract function deserializableProvider();

	/**
	 * @dataProvider nonDeserializableProvider
	 */
	public function testIsDeserializerForReturnsFalse( $nonSerializable ) {
		$this->assertFalse( $this->buildDeserializer()->isDeserializerFor( $nonSerializable ) );
	}

	/**
	 * @dataProvider nonDeserializableProvider
	 */
	public function testSerializeThrowsUnsupportedObjectException( $nonSerializable ) {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$this->buildDeserializer()->deserialize( $nonSerializable );
	}

	/**
	 * @return mixed[] things that aren't deserialized by the deserializer
	 */
	public abstract function nonDeserializableProvider();

	/**
	 * @dataProvider deserializationProvider
	 */
	public function testDeserialization( $object, $serialization ) {
		$this->assertEquals(
			$object,
			$this->buildDeserializer()->deserialize( $serialization )
		);
	}

	/**
	 * @return array an array of array( object deserialized, serialization)
	 */
	public abstract function deserializationProvider();
}
