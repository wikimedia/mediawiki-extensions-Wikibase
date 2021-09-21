<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Tests\Integration\Wikibase\InternalSerialization\TestFactoryBuilder;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\LegacyEntityDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyEntityDeserializerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp(): void {
		$this->deserializer = TestFactoryBuilder::newLegacyDeserializerFactory( $this )->newEntityDeserializer();
	}

	public function testGivenPropertySerialization_propertyIsReturned() {
		$serialization = [
			'entity' => 'P42',
			'datatype' => 'foo',
		];

		$deserialized = $this->deserializer->deserialize( $serialization );

		$this->assertInstanceOf( Property::class, $deserialized );
	}

	public function testGivenItemSerialization_itemIsReturned() {
		$serialization = [
			'entity' => 'Q42',
		];

		$deserialized = $this->deserializer->deserialize( $serialization );

		$this->assertInstanceOf( Item::class, $deserialized );
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_exceptionIsThrown( $serialization ) {
		$this->expectException( DeserializationException::class );
		$this->deserializer->deserialize( $serialization );
	}

	public function invalidSerializationProvider() {
		return [
			[ null ],
			[ 5 ],
			[ [ 'entity' => 'P42', 'datatype' => null ] ],
		];
	}

}
