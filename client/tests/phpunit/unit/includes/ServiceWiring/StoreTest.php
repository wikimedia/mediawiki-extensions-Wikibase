<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Store\Sql\DirectSqlStore;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Rdbms\ClientDomainDbFactory;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityIdLookup;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StoreTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.EntityIdParser',
			new ItemIdParser() );
		$this->mockService( 'WikibaseClient.EntityIdLookup',
			$this->createMock( EntityIdLookup::class ) );
		$this->mockService( 'WikibaseClient.WikibaseServices',
			$this->createMock( WikibaseServices::class ) );
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'sharedCacheKeyPrefix' => 'wikibase_shared/testdb',
				'sharedCacheKeyGroup' => 'testdb',
				'sharedCacheType' => CACHE_NONE,
				'sharedCacheDuration' => 60 * 60,
				'siteGlobalID' => 'test',
				'disabledUsageAspects' => [],
				'entityUsagePerPageLimit' => 100,
				'addEntityUsagesBatchSize' => 500,
				'enableImplicitDescriptionUsage' => false,
				'allowLocalShortDesc' => false,
			] ) );
		$this->mockService( 'WikibaseClient.TermBuffer',
			$this->createMock( TermBuffer::class ) );
		$itemAndPropertySource = new DatabaseEntitySource(
			'test',
			'testdb',
			[],
			'',
			'',
			'',
			''
		);
		$this->mockService( 'WikibaseClient.ItemAndPropertySource',
			$itemAndPropertySource );
		$repoDomainDbFactory = $this->createMock( RepoDomainDbFactory::class );
		$repoDomainDbFactory->expects( $this->once() )
			->method( 'newForEntitySource' )
			->with( $itemAndPropertySource );
		$this->mockService( 'WikibaseClient.RepoDomainDbFactory',
			$repoDomainDbFactory );
		$clientDomainDbFactory = $this->createMock( ClientDomainDbFactory::class );
		$clientDomainDbFactory->expects( $this->once() )
			->method( 'newLocalDb' );
		$this->mockService( 'WikibaseClient.ClientDomainDbFactory',
			$clientDomainDbFactory );

		$this->assertInstanceOf(
			DirectSqlStore::class,
			$this->getService( 'WikibaseClient.Store' )
		);
	}

}
