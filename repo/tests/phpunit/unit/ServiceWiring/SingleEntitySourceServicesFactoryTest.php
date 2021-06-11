<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use DataValues\Deserializers\DataValueDeserializer;
use Serializers\Serializer;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\SingleEntitySourceServicesFactory;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SingleEntitySourceServicesFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [] )
		);

		$this->mockService(
			'WikibaseRepo.EntityIdParser',
			new DispatchingEntityIdParser( [] )
		);

		$this->mockService(
			'WikibaseRepo.EntityIdComposer',
			new EntityIdComposer( [] )
		);

		$this->mockService(
			'WikibaseRepo.DataValueDeserializer',
			new DataValueDeserializer()
		);

		$this->serviceContainer->expects( $this->once() )
			->method( 'getNameTableStoreFactory' );

		$this->mockService(
			'WikibaseRepo.DataAccessSettings',
			new DataAccessSettings( 0 )
		);

		$this->mockService(
			'WikibaseRepo.LanguageFallbackChainFactory',
			$this->createMock( LanguageFallbackChainFactory::class )
		);

		$this->mockService(
			'WikibaseRepo.StorageEntitySerializer',
			$this->createMock( Serializer::class )
		);

		$this->mockService(
			'WikibaseRepo.RepoDomainDbFactory',
			$this->createMock( RepoDomainDbFactory::class )
		);

		$this->assertInstanceOf(
			SingleEntitySourceServicesFactory::class,
			$this->getService( 'WikibaseRepo.SingleEntitySourceServicesFactory' )
		);
	}

}
