<?php

namespace Wikibase\Lib\Tests\Serialization;

use DataValues\Deserializers\DataValueDeserializer;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;

/**
 * @covers Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory
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
			new PrefixMappingEntityIdParserFactory( new BasicEntityIdParser(), [] )
		);

		$deserializer = $factory->getDeserializer( '' );

		$result = $deserializer->deserialize( [ 'type' => 'wikibase-entityid', 'value' => [ 'id' => 'Q3' ] ] );
		$deserializedId = $result->getEntityId();

		$this->assertInstanceOf( ItemId::class, $deserializedId );
		$this->assertEquals( 'Q3', $deserializedId->getSerialization() );
	}

	public function testGivenForeignRepository_getDeserializerReturnsDeserializerPrefixingTheEntityIdValue() {
		$factory = new RepositorySpecificDataValueDeserializerFactory(
			new PrefixMappingEntityIdParserFactory( new BasicEntityIdParser(), [] )
		);

		$deserializer = $factory->getDeserializer( 'foo' );

		$result = $deserializer->deserialize( [ 'type' => 'wikibase-entityid', 'value' => [ 'id' => 'Q3' ] ] );
		$deserializedId = $result->getEntityId();

		$this->assertInstanceOf( ItemId::class, $deserializedId );
		$this->assertEquals( 'foo:Q3', $deserializedId->getSerialization() );
	}

	public function testGivenLocalRepository_getDeserializerReturnsDeserializerParsingNumericEntityIdValue() {
		$factory = new RepositorySpecificDataValueDeserializerFactory(
			new PrefixMappingEntityIdParserFactory( new BasicEntityIdParser(), [] )
		);

		$deserializer = $factory->getDeserializer( '' );

		$result = $deserializer->deserialize( [ 'type' => 'wikibase-entityid', 'value' => [ 'entity-type' => 'item', 'numeric-id' => 3 ] ] );
		$deserializedId = $result->getEntityId();

		$this->assertInstanceOf( ItemId::class, $deserializedId );
		$this->assertEquals( 'Q3', $deserializedId->getSerialization() );
	}

	public function testGivenForeignRepository_getDeserializerReturnsDeserializerThrowingExceptionOnNumericEntityIdValues() {
		$factory = new RepositorySpecificDataValueDeserializerFactory(
			new PrefixMappingEntityIdParserFactory( new BasicEntityIdParser(), [] )
		);

		$deserializer = $factory->getDeserializer( 'foo' );

		$this->setExpectedException( InvalidArgumentException::class );

		$deserializer->deserialize( [ 'type' => 'wikibase-entityid', 'value' => [ 'entity-type' => 'item', 'numeric-id' => 3 ] ] );
	}

}
