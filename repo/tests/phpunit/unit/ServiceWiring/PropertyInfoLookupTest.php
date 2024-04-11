<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyInfoLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.EntityIdComposer',
			$this->createStub( EntityIdComposer::class )
		);
		$this->mockService(
			'WikibaseRepo.RepoDomainDbFactory',
			$this->createStub( RepoDomainDbFactory::class )
		);
		$entitySourceDefinitions = $this->createStub( EntitySourceDefinitions::class );
		$entitySourceDefinitions->method( 'getDatabaseSourceForEntityType' )
			->willReturn( $this->createStub( DatabaseEntitySource::class ) );
		$this->mockService( 'WikibaseRepo.EntitySourceDefinitions', $entitySourceDefinitions );
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'sharedCacheKeyPrefix' => 'wikibase_shared/test',
				'sharedCacheKeyGroup' => 'test',
				'sharedCacheType' => CACHE_NONE,
				'sharedCacheDuration' => 60 * 60,
			] ) );

		$this->assertInstanceOf(
			PropertyInfoLookup::class,
			$this->getService( 'WikibaseRepo.PropertyInfoLookup' )
		);
	}

}
