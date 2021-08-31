<?php

namespace Tests\Wikibase\DataModel\Serializers;

use PHPUnit\Framework\TestCase;
use Serializers\DispatchableSerializer;
use Serializers\Exceptions\UnsupportedObjectException;

/**
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Thiemo Kreuz
 */
abstract class DispatchableSerializerTest extends TestCase {

	/**
	 * @return DispatchableSerializer
	 */
	abstract protected function buildSerializer();

	public function testImplementsDispatchableSerializerInterface() {
		$this->assertInstanceOf( DispatchableSerializer::class, $this->buildSerializer() );
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
	abstract public function serializableProvider();

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
		$this->expectException( UnsupportedObjectException::class );
		$this->buildSerializer()->serialize( $nonSerializable );
	}

	/**
	 * @return array[] things that aren't serialized by the serializer
	 */
	abstract public function nonSerializableProvider();

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( $serialization, $object ) {
		$this->assertSame( $serialization, $this->buildSerializer()->serialize( $object ) );
	}

	/**
	 * @return array[] an array of array( serialization, object to serialize)
	 */
	abstract public function serializationProvider();

}
