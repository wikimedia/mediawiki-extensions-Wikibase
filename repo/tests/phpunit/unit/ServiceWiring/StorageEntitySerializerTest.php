<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Serializers\DispatchableSerializer;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StorageEntitySerializerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$entity = $this->createMock( EntityDocument::class );
		$testSerializer = $this->createMock( DispatchableSerializer::class );
		$testSerializer->expects( $this->once() )
			->method( 'isSerializerFor' )
			->with( $entity )
			->willReturn( true );
		$serializerFactory = $this->createMock( SerializerFactory::class );
		$this->mockService( 'WikibaseRepo.BaseDataModelSerializerFactory', $serializerFactory );
		$callback = function ( $serializer ) use ( $serializerFactory, $testSerializer ) {
			$this->assertSame( $serializerFactory, $serializer );
			return $testSerializer;
		};
		$entityTypeDefinitions = new EntityTypeDefinitions( [
			'test' => [
				EntityTypeDefinitions::STORAGE_SERIALIZER_FACTORY_CALLBACK => $callback,
			],
		] );
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions', $entityTypeDefinitions );

		// service declares general Serializer return type;
		// we expect DispatchableSerializer for easier testing
		/** @var DispatchableSerializer $compactEntitySerializer */
		$storageEntitySerializer = $this->getService( 'WikibaseRepo.StorageEntitySerializer' );

		$this->assertInstanceOf( DispatchableSerializer::class, $storageEntitySerializer );
		$this->assertTrue( $storageEntitySerializer->isSerializerFor( $entity ) );
	}

}
