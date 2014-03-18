<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;

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
		$deserializer = $this->buildDeserializer();

		if ( $deserializer instanceof DispatchableDeserializer ) {
			$this->assertTrue( $deserializer->isDeserializerFor( $serializable ) );
		}
		else {
			$this->assertTrue( true );
		}
	}

	/**
	 * @return mixed[] things that are deserialized by the deserializer
	 */
	public abstract function deserializableProvider();

	/**
	 * @dataProvider nonDeserializableProvider
	 */
	public function testIsDeserializerForReturnsFalse( $nonSerializable ) {
		$deserializer = $this->buildDeserializer();

		if ( $deserializer instanceof DispatchableDeserializer ) {
			$this->assertFalse( $deserializer->isDeserializerFor( $nonSerializable ) );
		}
		else {
			$this->assertTrue( true );
		}
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
