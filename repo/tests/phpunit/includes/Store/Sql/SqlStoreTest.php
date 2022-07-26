<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Lib\Store\SiteLinkStore;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\SqlChangeStore;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\IdGenerator;
use Wikibase\Repo\Store\Sql\SqlStore;
use Wikibase\Repo\Store\Store;

/**
 * @covers \Wikibase\Repo\Store\Sql\SqlStore
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class SqlStoreTest extends MediaWikiIntegrationTestCase {

	public function newInstance() {
		$changeFactory = $this->createMock( EntityChangeFactory::class );

		$entityIdComposer = $this->createMock( EntityIdComposer::class );

		$prefetchingAccessor = $this->createMock( PrefetchingWikiPageEntityMetaDataAccessor::class );

		$wikibaseServices = $this->createMock( WikibaseServices::class );

		$wikibaseServices->method( 'getEntityPrefetcher' )
			->willReturn( $prefetchingAccessor );
		$wikibaseServices->method( 'getEntityRevisionLookup' )
			->willReturn( $this->createMock( EntityRevisionLookup::class ) );
		$wikibaseServices->method( 'getEntityStoreWatcher' )
			->willReturn( $this->createMock( EntityStoreWatcher::class ) );
		$wikibaseServices->method( 'getPropertyInfoLookup' )
			->willReturn( new MockPropertyInfoLookup() );

		return new SqlStore(
			$changeFactory,
			new ItemIdParser(),
			$entityIdComposer,
			$this->createMock( EntityIdLookup::class ),
			$this->createMock( EntityTitleStoreLookup::class ),
			new EntityNamespaceLookup( [] ),
			$this->createMock( IdGenerator::class ),
			$wikibaseServices,
			new DatabaseEntitySource( 'testsource', 'testdb', [], '', '', '', '' ),
			new SettingsArray( [
				'sharedCacheKeyPrefix' => 'wikibase_shared/testdb',
				'sharedCacheKeyGroup' => 'testdb',
				'sharedCacheType' => CACHE_NONE,
				'sharedCacheDuration' => 60 * 60,
			] )
		);
	}

	public function testNewSiteLinkStore() {
		$service = $this->newInstance()->newSiteLinkStore();
		$this->assertInstanceOf( SiteLinkStore::class, $service );
	}

	public function testGetEntityRedirectLookup() {
		$service = $this->newInstance()->getEntityRedirectLookup();
		$this->assertInstanceOf( EntityRedirectLookup::class, $service );
	}

	public function entityLoookupCacheProvider() {
		return [
			[ Store::LOOKUP_CACHING_ENABLED ],
			[ Store::LOOKUP_CACHING_DISABLED ],
			[ Store::LOOKUP_CACHING_RETRIEVE_ONLY ],
		];
	}

	/**
	 * @dataProvider entityLoookupCacheProvider
	 */
	public function testGetEntityLookup( $type ) {
		$service = $this->newInstance()->getEntityLookup( $type );

		$this->assertInstanceOf( EntityLookup::class, $service );
	}

	public function testGetEntityStoreWatcher() {
		$service = $this->newInstance()->getEntityStoreWatcher();
		$this->assertInstanceOf( EntityStoreWatcher::class, $service );
	}

	public function testGetEntityStore() {
		$service = $this->newInstance()->getEntityStore();
		$this->assertInstanceOf( EntityStore::class, $service );
	}

	/**
	 * @dataProvider entityLoookupCacheProvider
	 */
	public function testGetEntityRevisionLookup( $type ) {
		$service = $this->newInstance()->getEntityRevisionLookup( $type );

		$this->assertInstanceOf( EntityRevisionLookup::class, $service );
	}

	public function testGetPropertyInfoLookup() {
		$service = $this->newInstance()->getPropertyInfoLookup();
		$this->assertInstanceOf( PropertyInfoLookup::class, $service );
	}

	public function testGetPropertyInfoStore() {
		$service = $this->newInstance()->getPropertyInfoStore();
		$this->assertInstanceOf( PropertyInfoStore::class, $service );
	}

	public function testGetEntityPrefetcher() {
		$service = $this->newInstance()->getEntityPrefetcher();
		$this->assertInstanceOf( PrefetchingWikiPageEntityMetaDataAccessor::class, $service );
	}

	public function testGetEntityChangeLookup() {
		$service = $this->newInstance()->getEntityChangeLookup();
		$this->assertInstanceOf( EntityChangeLookup::class, $service );
	}

	public function testGetChangeStore() {
		$service = $this->newInstance()->getChangeStore();
		$this->assertInstanceOf( SqlChangeStore::class, $service );
	}

}
