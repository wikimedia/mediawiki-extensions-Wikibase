<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use DataValues\Deserializers\DataValueDeserializer;
use Serializers\Serializer;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\MultipleEntitySourceServices;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseServicesTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$entityTypeDefinitions = new EntityTypeDefinitions( [] );
		$source1 = new EntitySource(
			'source1',
			'source1',
			[],
			'',
			'',
			'',
			''
		);
		$source2 = new EntitySource(
			'source2',
			'source1',
			[],
			'',
			'',
			'',
			''
		);
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			$entityTypeDefinitions );
		$this->mockService( 'WikibaseRepo.EntitySourceDefinitions',
			new EntitySourceDefinitions( [ $source1, $source2 ], $entityTypeDefinitions ) );
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new DispatchingEntityIdParser( [] ) );
		$this->mockService( 'WikibaseRepo.EntityIdComposer',
			new EntityIdComposer( [] ) );
		$this->mockService( 'WikibaseRepo.DataValueDeserializer',
			new DataValueDeserializer() );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getNameTableStoreFactory' );
		$this->mockService( 'WikibaseRepo.DataAccessSettings',
			new DataAccessSettings( 0 ) );
		$this->mockService( 'WikibaseRepo.LanguageFallbackChainFactory',
			$this->createMock( LanguageFallbackChainFactory::class ) );
		$this->mockService( 'WikibaseRepo.StorageEntitySerializer',
			$this->createMock( Serializer::class ) );

		$this->assertInstanceOf(
			MultipleEntitySourceServices::class,
			$this->getService( 'WikibaseRepo.WikibaseServices' )
		);
	}

}
