<?php

declare( strict_types = 1 );

namespace Tests\Wikibase\DataModel\Serializers;

use PHPUnit\Framework\TestCase;
use Serializers\DispatchableSerializer;
use Serializers\Exceptions\UnsupportedObjectException;

/**
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Thiemo Kreuz
 */
abstract class DispatchableSerializerTestCase extends TestCase {

	abstract protected function buildSerializer(): DispatchableSerializer;

	public function testImplementsDispatchableSerializerInterface(): void {
		$this->assertInstanceOf( DispatchableSerializer::class, $this->buildSerializer() );
	}

	/**
	 * @dataProvider serializableProvider
	 */
	public function testIsSerializerForReturnsTrue( $serializable ): void {
		$this->assertTrue( $this->buildSerializer()->isSerializerFor( $serializable ) );
	}

	/**
	 * @return array[] things that are serialized by the serializer
	 */
	abstract public function serializableProvider(): array;

	/**
	 * @dataProvider nonSerializableProvider
	 */
	public function testIsSerializerForReturnsFalse( $nonSerializable ): void {
		$this->assertFalse( $this->buildSerializer()->isSerializerFor( $nonSerializable ) );
	}

	/**
	 * @dataProvider nonSerializableProvider
	 */
	public function testSerializeThrowsUnsupportedObjectException( $nonSerializable ): void {
		$this->expectException( UnsupportedObjectException::class );
		$this->buildSerializer()->serialize( $nonSerializable );
	}

	/**
	 * @return array[] things that aren't serialized by the serializer
	 */
	abstract public function nonSerializableProvider(): array;

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( $serialization, $object ): void {
		$this->assertEquals( $serialization, $this->buildSerializer()->serialize( $object ) );
	}

	/**
	 * @return array[] an array of array( serialization, object to serialize)
	 */
	abstract public function serializationProvider(): array;

}
