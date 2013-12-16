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
	 * @dataProvider deserializable
	 */
	public function testIsDeserializerForReturnsTrue( $serializable ) {
		$this->assertTrue( $this->buildDeserializer()->isDeserializerFor( $serializable ) );
	}

	/**
	 * @return mixed[] things that are deserialized by the deserializer
	 */
	public abstract function deserializable();

	/**
	 * @dataProvider nonDeserializable
	 */
	public function testIsDeserializerForReturnsFalse( $nonSerializable ) {
		$this->assertFalse( $this->buildDeserializer()->isDeserializerFor( $nonSerializable ) );
	}

	/**
	 * @dataProvider nonDeserializable
	 */
	public function testSerializeThrowsUnsupportedObjectException( $nonSerializable ) {
		$this->setExpectedException( 'Deserializers\Exceptions\UnsupportedObjectException' );
		$this->buildDeserializer()->deserialize( $nonSerializable );
	}

	/**
	 * @return mixed[] things that aren't deserialized by the deserializer
	 */
	public abstract function nonDeserializable();
}