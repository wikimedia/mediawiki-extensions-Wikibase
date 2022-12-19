<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Deserializers\DispatchableDeserializer;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AllTypesEntityDeserializerTest extends ServiceWiringTestCase {

	private function makeMockDeserializer( string $validSerialization ): callable {
		$deserializer = $this->createMock( DispatchableDeserializer::class );
		$deserializer->method( 'isDeserializerFor' )
			->willReturnCallback( function ( $serialization ) use ( $validSerialization ) {
				return $serialization == $validSerialization;
			} );

		return function () use ( $deserializer ): DispatchableDeserializer {
			return $deserializer;
		};
	}

	private function getEntityTypeDefinitions( array $validEntityTypes ): EntityTypeDefinitions {
		return new EntityTypeDefinitions( array_combine(
			$validEntityTypes,
			array_map( function ( string $entityTypeName ): array {
				return [
					EntityTypeDefinitions::DESERIALIZER_FACTORY_CALLBACK
					=> $this->makeMockDeserializer( $entityTypeName ),
				];
			}, $validEntityTypes )
		) );
	}

	public function testConstruction(): void {
		$validEntityTypes = [
			'something',
			'another',
		];

		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			$this->getEntityTypeDefinitions( $validEntityTypes )
		);

		$this->mockService(
			'WikibaseRepo.BaseDataModelDeserializerFactory',
			$this->createMock( DeserializerFactory::class )
		);

		/** @var DispatchableDeserializer $allTypesEntityDeserializer */
		$allTypesEntityDeserializer = $this->getService( 'WikibaseRepo.AllTypesEntityDeserializer' );

		$this->assertInstanceOf( DispatchableDeserializer::class, $allTypesEntityDeserializer );
		$this->assertFalse( $allTypesEntityDeserializer->isDeserializerFor( 'invalid-serialization' ) );

		foreach ( $validEntityTypes as $entityType ) {
			$this->assertTrue( $allTypesEntityDeserializer->isDeserializerFor( $entityType ) );
		}
	}

}
