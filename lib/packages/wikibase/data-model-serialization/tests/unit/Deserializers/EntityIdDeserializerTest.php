<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Exceptions\DeserializationException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\EntityIdDeserializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\DataModel\Deserializers\EntityIdDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class EntityIdDeserializerTest extends TestCase {

	private function buildDeserializer() {
		$entityIdParserMock = $this->createMock( EntityIdParser::class );
		$entityIdParserMock->expects( $this->any() )
			->method( 'parse' )
			->with( $this->equalTo( 'Q42' ) )
			->will( $this->returnValue( new ItemId( 'Q42' ) ) );

		return new EntityIdDeserializer( $entityIdParserMock );
	}

	/**
	 * @dataProvider nonDeserializableProvider
	 */
	public function testDeserializeThrowsDeserializationException( $nonDeserializable ) {
		$deserializer = $this->buildDeserializer();

		$this->expectException( DeserializationException::class );
		$deserializer->deserialize( $nonDeserializable );
	}

	public function nonDeserializableProvider() {
		return [
			[
				42,
			],
			[
				[],
			],
		];
	}

	/**
	 * @dataProvider deserializationProvider
	 */
	public function testDeserialization( $object, $serialization ) {
		$deserializer = $this->buildDeserializer();
		$this->assertEquals( $object, $deserializer->deserialize( $serialization ) );
	}

	public function deserializationProvider() {
		return [
			[
				new ItemId( 'Q42' ),
				'Q42',
			],
		];
	}

	public function testDeserializeWithEntityIdParsingException() {
		$entityIdParserMock = $this->createMock( EntityIdParser::class );
		$entityIdParserMock->expects( $this->any() )
			->method( 'parse' )
			->will( $this->throwException( new EntityIdParsingException() ) );
		$entityIdDeserializer = new EntityIdDeserializer( $entityIdParserMock );

		$this->expectException( DeserializationException::class );
		$entityIdDeserializer->deserialize( 'test' );
	}

}
