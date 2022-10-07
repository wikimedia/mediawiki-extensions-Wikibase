<?php

namespace Wikibase\Lib\Tests;

use Deserializers\Deserializer;
use Serializers\Serializer;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Deserializers\ItemDeserializer;
use Wikibase\DataModel\Deserializers\PropertyDeserializer;
use Wikibase\DataModel\Serializers\ItemSerializer;
use Wikibase\DataModel\Serializers\PropertySerializer;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @coversNothing
 */
class EntityTypesTest extends \PHPUnit\Framework\TestCase {

	private function getRegistry() {
		return require __DIR__ . '/../../WikibaseLib.entitytypes.php';
	}

	/**
	 * @param string $entityType
	 *
	 * @return SerializerFactory
	 */
	private function getSerializerFactory( $entityType ) {
		$serializerFactory = $this->createMock( SerializerFactory::class );
		$serializerFactory->expects( $this->once() )
			->method( 'new' . $entityType . 'Serializer' )
			->willReturn( [
				'item' => $this->createStub( ItemSerializer::class ),
				'property' => $this->createStub( PropertySerializer::class ),
			][$entityType] );

		return $serializerFactory;
	}

	/**
	 * @param string $entityType
	 *
	 * @return DeserializerFactory
	 */
	private function getDeserializerFactory( $entityType ) {
		$deserializerFactory = $this->createMock( DeserializerFactory::class );
		$deserializerFactory->expects( $this->once() )
			->method( 'new' . $entityType . 'Deserializer' )
			->willReturn( [
				'item' => $this->createStub( ItemDeserializer::class ),
				'property' => $this->createStub( PropertyDeserializer::class ),
			][$entityType] );

		return $deserializerFactory;
	}

	public function provideEntityTypes() {
		return array_map(
			function( $entityType ) {
				return [ $entityType ];
			},
			array_keys( $this->getRegistry() )
		);
	}

	public function testKnownEntityTypesSupported() {
		$entityTypes = $this->provideEntityTypes();

		$this->assertContains( [ 'item' ], $entityTypes );
		$this->assertContains( [ 'property' ], $entityTypes );
	}

	/**
	 * @dataProvider provideEntityTypes
	 */
	public function testSerializerFactory( $entityType ) {
		$registry = $this->getRegistry();
		$serializerFactory = $this->getSerializerFactory( $entityType );

		$this->assertArrayHasKey( $entityType, $registry );
		$this->assertArrayHasKey( EntityTypeDefinitions::SERIALIZER_FACTORY_CALLBACK, $registry[$entityType] );

		$callback = $registry[$entityType][EntityTypeDefinitions::SERIALIZER_FACTORY_CALLBACK];

		$this->assertIsCallable( $callback );

		$this->assertInstanceOf(
			Serializer::class,
			call_user_func( $callback, $serializerFactory )
		);
	}

	/**
	 * @dataProvider provideEntityTypes
	 */
	public function testDeserializerFactory( $entityType ) {
		$registry = $this->getRegistry();
		$deserializerFactory = $this->getDeserializerFactory( $entityType );

		$this->assertArrayHasKey( $entityType, $registry );
		$this->assertArrayHasKey( EntityTypeDefinitions::DESERIALIZER_FACTORY_CALLBACK, $registry[$entityType] );

		$callback = $registry[$entityType][EntityTypeDefinitions::DESERIALIZER_FACTORY_CALLBACK];

		$this->assertIsCallable( $callback );

		$this->assertInstanceOf(
			Deserializer::class,
			call_user_func( $callback, $deserializerFactory )
		);
	}

}
