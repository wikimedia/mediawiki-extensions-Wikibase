<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\EntityIdDeserializer;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\DataModel\Deserializers\EntityIdDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class EntityIdDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		$entityIdParserMock = $this->getMock( '\Wikibase\DataModel\Entity\EntityIdParser' );
		$entityIdParserMock->expects( $this->any() )
			->method( 'parse' )
			->with( $this->equalTo( 'Q42' ) )
			->will( $this->returnValue( new ItemId( 'Q42' ) ) );

		return new EntityIdDeserializer( $entityIdParserMock );
	}

	public function deserializableProvider() {
		return array(
			array(
				'Q42'
			),
		);
	}

	public function nonDeserializableProvider() {
		return array(
			array(
				42
			),
			array(
				array()
			),
		);
	}

	public function deserializationProvider() {
		return array(
			array(
				new ItemId( 'Q42' ),
				'Q42'
			),
		);
	}

	public function testDeserializeWithEntityIdParsingException() {
		$entityIdParserMock = $this->getMock( '\Wikibase\DataModel\Entity\EntityIdParser' );
		$entityIdParserMock->expects( $this->any() )
			->method( 'parse' )
			->will( $this->throwException( new EntityIdParsingException() ) );
		$entityIdDeserializer = new EntityIdDeserializer( $entityIdParserMock );

		$this->setExpectedException( '\Deserializers\Exceptions\DeserializationException' );
		$entityIdDeserializer->deserialize( 'test' );
	}
}