<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Exceptions\DeserializationException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Deserializers\EntityIdDeserializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\DataModel\Deserializers\EntityIdDeserializer
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 */
class EntityIdDeserializerTest extends PHPUnit_Framework_TestCase {

	private function buildDeserializer() {
		$entityIdParserMock = $this->getMock( EntityIdParser::class );
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

		$this->setExpectedException( DeserializationException::class );
		$deserializer->deserialize( $nonDeserializable );
	}

	public function nonDeserializableProvider() {
		return [
			[
				42
			],
			[
				[]
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
				'Q42'
			],
		];
	}

	public function testDeserializeWithEntityIdParsingException() {
		$entityIdParserMock = $this->getMock( EntityIdParser::class );
		$entityIdParserMock->expects( $this->any() )
			->method( 'parse' )
			->will( $this->throwException( new EntityIdParsingException() ) );
		$entityIdDeserializer = new EntityIdDeserializer( $entityIdParserMock );

		$this->setExpectedException( DeserializationException::class );
		$entityIdDeserializer->deserialize( 'test' );
	}

}
