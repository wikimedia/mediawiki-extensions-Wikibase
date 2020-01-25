<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use PHPUnit\Framework\TestCase;

/**
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Thiemo Kreuz
 */
abstract class DispatchableDeserializerTest extends TestCase {

	/**
	 * @return DispatchableDeserializer
	 */
	abstract protected function buildDeserializer();

	public function testImplementsDispatchableDeserializerInterface() {
		$this->assertInstanceOf( DispatchableDeserializer::class, $this->buildDeserializer() );
	}

	/**
	 * @dataProvider deserializableProvider
	 */
	public function testIsDeserializerForReturnsTrue( $deserializable ) {
		$this->assertTrue( $this->buildDeserializer()->isDeserializerFor( $deserializable ) );
	}

	/**
	 * @return array[] things that are deserialized by the deserializer
	 */
	abstract public function deserializableProvider();

	/**
	 * @dataProvider nonDeserializableProvider
	 */
	public function testIsDeserializerForReturnsFalse( $nonDeserializable ) {
		$this->assertFalse( $this->buildDeserializer()->isDeserializerFor( $nonDeserializable ) );
	}

	/**
	 * @dataProvider nonDeserializableProvider
	 */
	public function testDeserializeThrowsDeserializationException( $nonDeserializable ) {
		$deserializer = $this->buildDeserializer();

		$this->expectException( DeserializationException::class );
		$deserializer->deserialize( $nonDeserializable );
	}

	/**
	 * @return array[] things that aren't deserialized by the deserializer
	 */
	abstract public function nonDeserializableProvider();

	/**
	 * @dataProvider deserializationProvider
	 */
	public function testDeserialization( $object, $serialization ) {
		$this->assertEquals( $object, $this->buildDeserializer()->deserialize( $serialization ) );
	}

	/**
	 * @return array[] an array of array( object deserialized, serialization )
	 */
	abstract public function deserializationProvider();

}
