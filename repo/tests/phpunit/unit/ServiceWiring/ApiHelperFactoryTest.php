<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use HashSiteStore;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\HashSiteLinkStore;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiHelperFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$store = $this->createMock( Store::class );
		$store->expects( $this->once() )
			->method( 'getEntityRevisionLookup' )
			->with( Store::LOOKUP_CACHING_DISABLED )
			->willReturn( $this->createMock( EntityRevisionLookup::class ) );
		$store->expects( $this->once() )
			->method( 'getEntityByLinkedTitleLookup' )
			->willReturn( new HashSiteLinkStore() );
		$this->mockService( 'WikibaseRepo.Store',
			$store );
		$this->mockService( 'WikibaseRepo.EntityTitleStoreLookup',
			$this->createMock( EntityTitleStoreLookup::class ) );
		$this->mockService( 'WikibaseRepo.ExceptionLocalizer',
			$this->createMock( ExceptionLocalizer::class ) );
		$this->mockService( 'WikibaseRepo.PropertyDataTypeLookup',
			new InMemoryDataTypeLookup() );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' )
			->willReturn( new HashSiteStore() );
		$this->mockService( 'WikibaseRepo.SummaryFormatter',
			$this->createMock( SummaryFormatter::class ) );
		$this->mockService( 'WikibaseRepo.EditEntityFactory',
			$this->createMock( MediawikiEditEntityFactory::class ) );
		$this->mockService( 'WikibaseRepo.BaseDataModelSerializerFactory',
			$this->createMock( SerializerFactory::class ) );
		$this->mockService( 'WikibaseRepo.AllTypesEntitySerializer',
			$this->createMock( Serializer::class ) );
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new ItemIdParser() );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getPermissionManager' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getRevisionLookup' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getTitleFactory' );
		$this->mockService( 'WikibaseRepo.EntityFactory',
			new EntityFactory( [] ) );
		$this->mockService( 'WikibaseRepo.EntityStore',
			$this->createMock( EntityStore::class ) );

		$this->assertInstanceOf(
			ApiHelperFactory::class,
			$this->getService( 'WikibaseRepo.ApiHelperFactory' )
		);
	}

}
