<?php

namespace Tests\Wikibase\DataModel\Serializers;

use PHPUnit_Framework_TestCase;
use Serializers\DispatchableSerializer;

/**
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Thiemo Mättig
 */
abstract class DispatchableSerializerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return DispatchableSerializer
	 */
	protected abstract function buildSerializer();

	public function testImplementsDispatchableSerializerInterface() {
		$this->assertInstanceOf( 'Serializers\DispatchableSerializer', $this->buildSerializer() );
	}

	/**
	 * @dataProvider serializableProvider
	 */
	public function testIsSerializerForReturnsTrue( $serializable ) {
		$this->assertTrue( $this->buildSerializer()->isSerializerFor( $serializable ) );
	}

	/**
	 * @return array[] things that are serialized by the serializer
	 */
	public abstract function serializableProvider();

	/**
	 * @dataProvider nonSerializableProvider
	 */
	public function testIsSerializerForReturnsFalse( $nonSerializable ) {
		$this->assertFalse( $this->buildSerializer()->isSerializerFor( $nonSerializable ) );
	}

	/**
	 * @dataProvider nonSerializableProvider
	 */
	public function testSerializeThrowsUnsupportedObjectException( $nonSerializable ) {
		$this->setExpectedException( 'Serializers\Exceptions\UnsupportedObjectException' );
		$this->buildSerializer()->serialize( $nonSerializable );
	}

	/**
	 * @return array[] things that aren't serialized by the serializer
	 */
	public abstract function nonSerializableProvider();

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( $serialization, $object ) {
		$this->assertSame( $serialization, $this->buildSerializer()->serialize( $object ) );
	}

	/**
	 * @return array[] an array of array( serialization, object to serialize)
	 */
	public abstract function serializationProvider();

}
