<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Serializer;

/**
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
abstract class SerializerBaseTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return Serializer
	 */
	public abstract function buildSerializer();

	public function testImplementsSerializerInterface() {
		$this->assertInstanceOf( 'Serializers\Serializer', $this->buildSerializer() );
	}

	/**
	 * @dataProvider serializableProvider
	 */
	public function testIsSerializerForReturnsTrue( $serializable ) {
		$serializer = $this->buildSerializer();

		if ( $serializer instanceof DispatchableSerializer ) {
			$this->assertTrue( $serializer->isSerializerFor( $serializable ) );
		}
		else {
			$this->assertTrue( true );
		}
	}

	/**
	 * @return mixed[] things that are serialized by the serializer
	 */
	public abstract function serializableProvider();

	/**
	 * @dataProvider nonSerializableProvider
	 */
	public function testIsSerializerForReturnsFalse( $nonSerializable ) {
		$serializer = $this->buildSerializer();

		if ( $serializer instanceof DispatchableSerializer ) {
			$this->assertFalse( $serializer->isSerializerFor( $nonSerializable ) );
		}
		else {
			$this->assertTrue( true );
		}
	}

	/**
	 * @dataProvider nonSerializableProvider
	 */
	public function testSerializeThrowsUnsupportedObjectException( $nonSerializable ) {
		$this->setExpectedException( 'Serializers\Exceptions\UnsupportedObjectException' );
		$this->buildSerializer()->serialize( $nonSerializable );
	}

	/**
	 * @return mixed[] things that aren't serialized by the serializer
	 */
	public abstract function nonSerializableProvider();

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( $serialization, $object ) {
		$this->assertEquals(
			$serialization,
			$this->buildSerializer()->serialize( $object )
		);
	}

	/**
	 * @return array an array of array( serialization, object to serialize)
	 */
	public abstract function serializationProvider();
}
