<?php

namespace Wikibase\Lib\Tests\Serialization;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\IllegalValueException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;

/**
 * @covers Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class RepositorySpecificDataValueDeserializerFactoryTest extends \PHPUnit_Framework_TestCase {

	private function getDummyIdParserFactory() {
		return new PrefixMappingEntityIdParserFactory(
			$this->getMock( EntityIdParser::class ),
			[]
		);
	}

	public function testGetDeserializerReturnsInstanceOfDataValueDeserializer() {
		$factory = new \Wikibase\Edrsf\RepositorySpecificDataValueDeserializerFactory( $this->getDummyIdParserFactory() );

		$this->assertInstanceOf( DataValueDeserializer::class, $factory->getDeserializer( '' ) );
		$this->assertInstanceOf( DataValueDeserializer::class, $factory->getDeserializer( 'foo' ) );
	}

	public function testGetDeserializerReusesInstanceOverMultipleCalls() {
		$factory = new \Wikibase\Edrsf\RepositorySpecificDataValueDeserializerFactory( $this->getDummyIdParserFactory() );

		$deserializerOne = $factory->getDeserializer( 'foo' );
		$deserializerTwo = $factory->getDeserializer( 'foo' );

		$this->assertSame( $deserializerOne, $deserializerTwo );
	}

	public function testGivenLocalRepository_getDeserializerReturnsDeserializerReturningUnchangedEntityIdValue() {
		$factory = new \Wikibase\Edrsf\RepositorySpecificDataValueDeserializerFactory(
			new PrefixMappingEntityIdParserFactory( new ItemIdParser(), [] )
		);

		$deserializer = $factory->getDeserializer( '' );

		$result = $deserializer->deserialize( [ 'type' => 'wikibase-entityid', 'value' => [ 'id' => 'Q3' ] ] );
		$deserializedId = $result->getEntityId();

		$this->assertInstanceOf( ItemId::class, $deserializedId );
		$this->assertEquals( 'Q3', $deserializedId->getSerialization() );
	}

	public function testGivenForeignRepository_getDeserializerReturnsDeserializerPrefixingTheEntityIdValue() {
		$factory = new \Wikibase\Edrsf\RepositorySpecificDataValueDeserializerFactory(
			new PrefixMappingEntityIdParserFactory( new ItemIdParser(), [] )
		);

		$deserializer = $factory->getDeserializer( 'foo' );

		$result = $deserializer->deserialize( [ 'type' => 'wikibase-entityid', 'value' => [ 'id' => 'Q3' ] ] );
		$deserializedId = $result->getEntityId();

		$this->assertInstanceOf( ItemId::class, $deserializedId );
		$this->assertEquals( 'foo:Q3', $deserializedId->getSerialization() );
	}

	public function testGivenLocalRepository_getDeserializerReturnsDeserializerParsingNumericEntityIdValue() {
		$factory = new \Wikibase\Edrsf\RepositorySpecificDataValueDeserializerFactory(
			new PrefixMappingEntityIdParserFactory( new ItemIdParser(), [] )
		);

		$deserializer = $factory->getDeserializer( '' );

		$result = $deserializer->deserialize( [ 'type' => 'wikibase-entityid', 'value' => [ 'entity-type' => 'item', 'numeric-id' => 3 ] ] );
		$deserializedId = $result->getEntityId();

		$this->assertInstanceOf( ItemId::class, $deserializedId );
		$this->assertEquals( 'Q3', $deserializedId->getSerialization() );
	}

	public function testGivenForeignRepository_getDeserializerReturnsDeserializerThrowingExceptionOnNumericEntityIdValues() {
		$factory = new \Wikibase\Edrsf\RepositorySpecificDataValueDeserializerFactory(
			new PrefixMappingEntityIdParserFactory( new ItemIdParser(), [] )
		);

		$deserializer = $factory->getDeserializer( 'foo' );

		$this->setExpectedException( IllegalValueException::class );

		$deserializer->deserialize( [ 'type' => 'wikibase-entityid', 'value' => [ 'entity-type' => 'item', 'numeric-id' => 3 ] ] );
	}

}
