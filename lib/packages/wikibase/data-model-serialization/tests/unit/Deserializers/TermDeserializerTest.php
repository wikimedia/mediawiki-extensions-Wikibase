<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Exceptions\DeserializationException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Term\Term;

/**
 * @covers Wikibase\DataModel\Deserializers\TermDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class TermDeserializerTest extends TestCase {

	/**
	 * @dataProvider nonDeserializableProvider
	 */
	public function testDeserializeThrowsDeserializationException( $nonDeserializable ) {
		$deserializer = new TermDeserializer();

		$this->expectException( DeserializationException::class );
		$deserializer->deserialize( $nonDeserializable );
	}

	/**
	 * @return array[] things that aren't deserialized by the deserializer
	 */
	public function nonDeserializableProvider() {
		return [
			'must be an array' => [ new \stdClass() ],
			'must contain language' => [ [
				'value' => 'FooBar',
			] ],
			'must contain value' => [ [
				'language' => 'de',
			] ],
			'language must be string' => [ [
				'language' => 123,
				'value' => 'FooBar',
			] ],
			'value must be string' => [ [
				'language' => 'de',
				'value' => 999,
			] ],
			'must not contain source' => [ [
				'language' => 'fr',
				'value' => 'Fr to DE hehe',
				'source' => 'de',
			] ],
		];
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
		return [
			[
				new Term( 'en', 'Value' ),
				[
					'language' => 'en',
					'value' => 'Value',
				],
			],
		];
	}

}
