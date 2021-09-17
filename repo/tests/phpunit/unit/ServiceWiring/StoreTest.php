<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\IdGenerator;
use Wikibase\Repo\Store\Sql\SqlStore;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StoreTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.EntityChangeFactory',
			$this->createMock( EntityChangeFactory::class ) );
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new ItemIdParser() );
		$this->mockService( 'WikibaseRepo.EntityIdComposer',
			new EntityIdComposer( [] ) );
		$this->mockService( 'WikibaseRepo.EntityIdLookup',
			$this->createMock( EntityIdLookup::class ) );
		$this->mockService( 'WikibaseRepo.EntityTitleStoreLookup',
			$this->createMock( EntityTitleStoreLookup::class ) );
		$this->mockService( 'WikibaseRepo.EntityNamespaceLookup',
			new EntityNamespaceLookup( [] ) );
		$this->mockService( 'WikibaseRepo.IdGenerator',
			$this->createMock( IdGenerator::class ) );
		$this->mockService( 'WikibaseRepo.WikibaseServices',
			$this->createMock( WikibaseServices::class ) );
		$this->mockService( 'WikibaseRepo.LocalEntitySource',
			$this->createMock( DatabaseEntitySource::class ) );
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'sharedCacheKeyPrefix' => 'wikibase_shared/test',
				'sharedCacheKeyGroup' => 'test',
				'sharedCacheType' => CACHE_NONE,
				'sharedCacheDuration' => 60 * 60,
			] ) );

		$this->assertInstanceOf(
			SqlStore::class,
			$this->getService( 'WikibaseRepo.Store' )
		);
	}

}
