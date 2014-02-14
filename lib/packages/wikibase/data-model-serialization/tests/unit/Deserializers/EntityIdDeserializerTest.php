<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\EntityIdDeserializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\DataModel\Deserializers\EntityIdDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class EntityIdDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		return new EntityIdDeserializer( new BasicEntityIdParser() );
	}

	public function deserializableProvider() {
		return array(
			array(
				'q42'
			),
			array(
				'p43'
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
			array(
				array(
					'id' => 'P10'
				)
			),
		);
	}

	public function deserializationProvider() {
		return array(
			array(
				new ItemId( 'Q42' ),
				'Q42'
			),
			array(
				new ItemId( 'Q42' ),
				'q42'
			),
			array(
				new PropertyId( 'P42' ),
				'P42'
			),
		);
	}

	/**
	 * @dataProvider entityIdParsingExceptionProvider
	 */
	public function testEntityIdParsingException( $serialization ) {
		$this->setExpectedException( '\Deserializers\Exceptions\DeserializationException' );
		$this->buildDeserializer()->deserialize( $serialization );
	}

	public function entityIdParsingExceptionProvider() {
		return array(
			array(
				'test'
			),
			array(
				'qp42'
			),
		);
	}
}