<?php

namespace Wikibase\Lib\Tests\Serialization;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Exceptions\DeserializationException;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\Lib\DataValue\UnmappedEntityIdValue;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;

/**
 * @covers Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RepositorySpecificDataValueDeserializerFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	private function getDummyIdParserFactory() {
		return new PrefixMappingEntityIdParserFactory( new ItemIdParser(), [] );
	}

	public function testGetDeserializerReturnsInstanceOfDataValueDeserializer() {
		$factory = new RepositorySpecificDataValueDeserializerFactory( $this->getDummyIdParserFactory() );

		$this->assertInstanceOf( DataValueDeserializer::class, $factory->getDeserializer( '' ) );
		$this->assertInstanceOf( DataValueDeserializer::class, $factory->getDeserializer( 'foo' ) );
	}

	public function testGetDeserializerReusesInstanceOverMultipleCalls() {
		$factory = new RepositorySpecificDataValueDeserializerFactory( $this->getDummyIdParserFactory() );

		$deserializerOne = $factory->getDeserializer( 'foo' );
		$deserializerTwo = $factory->getDeserializer( 'foo' );

		$this->assertSame( $deserializerOne, $deserializerTwo );
	}

	public function testGivenLocalRepository_getDeserializerReturnsDeserializerReturningUnchangedEntityIdValue() {
		$factory = new RepositorySpecificDataValueDeserializerFactory(
			new PrefixMappingEntityIdParserFactory( new ItemIdParser(), [] )
		);

		$deserializer = $factory->getDeserializer( '' );

		$result = $deserializer->deserialize( [ 'type' => 'wikibase-entityid', 'value' => [ 'id' => 'Q3' ] ] );
		$deserializedId = $result->getEntityId();

		$this->assertInstanceOf( ItemId::class, $deserializedId );
		$this->assertEquals( 'Q3', $deserializedId->getSerialization() );
	}

	public function testGivenForeignRepository_getDeserializerReturnsDeserializerPrefixingTheEntityIdValue() {
		$factory = new RepositorySpecificDataValueDeserializerFactory(
			new PrefixMappingEntityIdParserFactory( new ItemIdParser(), [] )
		);

		$deserializer = $factory->getDeserializer( 'foo' );

		$result = $deserializer->deserialize( [ 'type' => 'wikibase-entityid', 'value' => [ 'id' => 'Q3' ] ] );
		$deserializedId = $result->getEntityId();

		$this->assertInstanceOf( ItemId::class, $deserializedId );
		$this->assertEquals( 'foo:Q3', $deserializedId->getSerialization() );
	}

	public function testGivenLocalRepository_getDeserializerReturnsDeserializerParsingNumericEntityIdValue() {
		$factory = new RepositorySpecificDataValueDeserializerFactory(
			new PrefixMappingEntityIdParserFactory( new ItemIdParser(), [] )
		);

		$deserializer = $factory->getDeserializer( '' );

		$result = $deserializer->deserialize( [ 'type' => 'wikibase-entityid', 'value' => [ 'entity-type' => 'item', 'numeric-id' => 3 ] ] );
		$deserializedId = $result->getEntityId();

		$this->assertInstanceOf( ItemId::class, $deserializedId );
		$this->assertEquals( 'Q3', $deserializedId->getSerialization() );
	}

	public function testGivenForeignRepository_getDeserializerReturnsDeserializerThrowingExceptionOnNumericEntityIdValues() {
		$factory = new RepositorySpecificDataValueDeserializerFactory(
			new PrefixMappingEntityIdParserFactory( new ItemIdParser(), [] )
		);

		$deserializer = $factory->getDeserializer( 'foo' );

		$this->setExpectedException( DeserializationException::class );

		$deserializer->deserialize( [ 'type' => 'wikibase-entityid', 'value' => [ 'entity-type' => 'item', 'numeric-id' => 3 ] ] );
	}

	public function testReturnsDeserializerParsingEntityIdValuesOfUnmappedEntityType() {
		$factory = new RepositorySpecificDataValueDeserializerFactory(
			new PrefixMappingEntityIdParserFactory( new ItemIdParser(), [] )
		);

		$deserializer = $factory->getDeserializer( '' );

		$result = $deserializer->deserialize( [ 'type' => 'wikibase-entityid', 'value' => [ 'id' => 'X303' ] ] );

		$this->assertInstanceOf( UnmappedEntityIdValue::class, $result );
		$this->assertSame( 'X303', $result->getValue() );
	}

}
