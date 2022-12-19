<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use DataValues\Deserializers\DataValueDeserializer;
use Serializers\Serializer;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\MultipleEntitySourceServices;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\SubEntityTypesMapper;
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
		$entitySources = [
			new DatabaseEntitySource(
				'source1',
				'source1',
				[],
				'',
				'',
				'',
				''
			),
			new DatabaseEntitySource(
				'source2',
				'source1',
				[],
				'',
				'',
				'',
				''
			),
		];

		$this->mockService( 'WikibaseRepo.EntitySourceDefinitions',
			new EntitySourceDefinitions( $entitySources, new SubEntityTypesMapper( [] ) ) );
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
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [] ) );
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory',
			$this->createMock( RepoDomainDbFactory::class ) );

		$this->assertInstanceOf(
			MultipleEntitySourceServices::class,
			$this->getService( 'WikibaseRepo.WikibaseServices' )
		);
	}

}
