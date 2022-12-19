<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Serializers\DispatchableSerializer;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CompactEntitySerializerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$entity = $this->createMock( EntityDocument::class );
		$testSerializer = $this->createMock( DispatchableSerializer::class );
		$testSerializer->expects( $this->once() )
			->method( 'isSerializerFor' )
			->with( $entity )
			->willReturn( true );
		$serializerFactory = $this->createMock( SerializerFactory::class );
		$this->mockService( 'WikibaseClient.CompactBaseDataModelSerializerFactory', $serializerFactory );
		$callback = function ( $serializer ) use ( $serializerFactory, $testSerializer ) {
			$this->assertSame( $serializerFactory, $serializer );
			return $testSerializer;
		};
		$entityTypeDefinitions = new EntityTypeDefinitions( [
			'test' => [
				EntityTypeDefinitions::SERIALIZER_FACTORY_CALLBACK => $callback,
			],
		] );
		$this->mockService( 'WikibaseClient.EntityTypeDefinitions', $entityTypeDefinitions );

		// service declares general Serializer return type;
		// we expect DispatchableSerializer for easier testing
		/** @var DispatchableSerializer $compactEntitySerializer */
		$compactEntitySerializer = $this->getService( 'WikibaseClient.CompactEntitySerializer' );

		$this->assertInstanceOf( DispatchableSerializer::class, $compactEntitySerializer );
		$this->assertTrue( $compactEntitySerializer->isSerializerFor( $entity ) );
	}

}
