<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Term\Term;

/**
 * @covers Wikibase\DataModel\Deserializers\TermDeserializer
 *
 * @licence GNU GPL v2+
 * @author Addshore
 */
class TermDeserializerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider nonDeserializableProvider
	 */
	public function testDeserializeThrowsDeserializationException( $nonDeserializable ) {
		$deserializer = new TermDeserializer();
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$deserializer->deserialize( $nonDeserializable );
	}

	/**
	 * @return array[] things that aren't deserialized by the deserializer
	 */
	public function nonDeserializableProvider() {
		return array(
			'must be an array' => array( new \stdClass() ),
			'must contain language' => array( array(
				'value' => 'FooBar',
			) ),
			'must contain value' => array( array(
				'language' => 'de',
			) ),
			'language must be string' => array( array(
				'language' => 123,
				'value' => 'FooBar',
			) ),
			'value must be string' => array( array(
				'language' => 'de',
				'value' => 999,
			) ),
			'must not contain source' => array( array(
				'language' => 'fr',
				'value' => 'Fr to DE hehe',
				'source' => 'de',
			) ),
		);
	}

	/**
	 * @dataProvider deserializationProvider
	 */
	public function testDeserialization( $object, $serialization ) {
		$deserializer = new TermDeserializer();
		$this->assertEquals( $object, $deserializer->deserialize( $serialization ) );
	}

	/**
	 * @return array[] an array of array( object deserialized, serialization )
	 */
	public function deserializationProvider() {
		return array(
			array(
				new Term( 'en', 'Value' ),
				array(
					'language' => 'en',
					'value' => 'Value',
				),
			),
		);
	}

}
