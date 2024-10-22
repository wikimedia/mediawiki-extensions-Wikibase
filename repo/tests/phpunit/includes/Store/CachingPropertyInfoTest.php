<?php

namespace Wikibase\Repo\Tests\Store;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Store\CacheAwarePropertyInfoStore;
use Wikibase\Lib\Store\CachingPropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ObjectCache\HashBagOStuff;
use Wikimedia\ObjectCache\WANObjectCache;

/**
 * Integration tests for both CacheAwarePropertyInfoStore and CachingPropertyInfoLookup.
 * These are hard to test separately as one is needed for observations about the other,
 * or one is needed to add test data for the other.
 *
 * @covers \Wikibase\Lib\Store\CacheAwarePropertyInfoStore
 * @covers \Wikibase\Lib\Store\CachingPropertyInfoLookup
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibasePropertyInfo
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class CachingPropertyInfoTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	private function newPropertyInfoTable() {
		return new PropertyInfoTable(
			$this->getEntityComposer(),
			$this->getRepoDomainDb(),
			true
		);
	}

	private function getEntityComposer() {
		return new EntityIdComposer( [
			Property::ENTITY_TYPE => function( $uniquePart ) {
				return new NumericPropertyId( 'P' . $uniquePart );
			},
		] );
	}

	private function newCache() {
		return new WANObjectCache( [ 'cache' => new HashBagOStuff() ] );
	}

	private function getServices() {
		$table = $this->newPropertyInfoTable();
		$cache = $this->newCache();
		$store = new CacheAwarePropertyInfoStore(
			$table,
			$cache,
			3600,
			'group'
		);
		$lookup = new CachingPropertyInfoLookup(
			$table, $cache, 'group', 3600
		);

		return [ $table, $cache, $store, $lookup ];
	}

	private function getInfo( $suffix = '' ) {
		return [
			PropertyInfoLookup::KEY_DATA_TYPE => 'type' . $suffix,
		];
	}

	public function testStoringPropertyIsPersistedAndRetrievable() {
		/**
		 * @var PropertyInfoTable $table
		 * @var WANObjectCache $cache
		 * @var CacheAwarePropertyInfoStore $store
		 * @var CachingPropertyInfoLookup $lookup
		 */
		[ $table, $cache, $store, $lookup ] = $this->getServices();

		$p1 = new NumericPropertyId( 'P1' );
		$p1Info = $this->getInfo();
		$p1Type = $p1Info[PropertyInfoLookup::KEY_DATA_TYPE];
		$store->setPropertyInfo( $p1, $p1Info );

		// The table should now have the property that we stored
		$this->assertCount( 1, $table->getAllPropertyInfo() );
		$this->assertEquals( $p1Info, $table->getPropertyInfo( $p1 ) );
		$this->assertEquals( [ 'P1' => $p1Info ], $table->getAllPropertyInfo() );
		$this->assertEquals( [ 'P1' => $p1Info ], $table->getPropertyInfoForDataType( $p1Type ) );

		// Using the caching store should return the property info
		$this->assertCount( 1, $lookup->getAllPropertyInfo() );
		$this->assertEquals( $p1Info, $lookup->getPropertyInfo( $p1 ) );
		$this->assertEquals( [ 'P1' => $p1Info ], $lookup->getAllPropertyInfo() );
		$this->assertEquals( [ 'P1' => $p1Info ], $lookup->getPropertyInfoForDataType( $p1Type ) );
	}

	public function testStoringChangedPropertyIsPersistedAndRetrievable() {
		/**
		 * @var PropertyInfoTable $table
		 * @var WANObjectCache $cache
		 * @var CacheAwarePropertyInfoStore $store
		 * @var CachingPropertyInfoLookup $lookup
		 */
		[ $table, $cache, $store, $lookup ] = $this->getServices();

		$p1 = new NumericPropertyId( 'P1' );
		$p1Info = $this->getInfo();
		$p1Type = $p1Info[PropertyInfoLookup::KEY_DATA_TYPE];
		$p1InfoTwo = $this->getInfo( '-2' );
		$p1TypeTwo = $p1InfoTwo[PropertyInfoLookup::KEY_DATA_TYPE];
		$store->setPropertyInfo( $p1, $p1Info );

		// Retrieve everything once to make sure it is cached
		$this->assertCount( 1, $lookup->getAllPropertyInfo() );
		$this->assertEquals( $p1Info, $lookup->getPropertyInfo( $p1 ) );
		$this->assertEquals( [ 'P1' => $p1Info ], $lookup->getAllPropertyInfo() );
		$this->assertEquals( [ 'P1' => $p1Info ], $lookup->getPropertyInfoForDataType( $p1Type ) );

		// Alter what is stored
		$store->setPropertyInfo( $p1, $p1InfoTwo );

		// Make sure the change has been persisted
		$this->assertCount( 1, $table->getAllPropertyInfo() );
		$this->assertEquals( $p1InfoTwo, $table->getPropertyInfo( $p1 ) );
		$this->assertEquals( [ 'P1' => $p1InfoTwo ], $table->getAllPropertyInfo() );
		$this->assertEquals( [], $table->getPropertyInfoForDataType( $p1Type ) );
		$this->assertEquals( [ 'P1' => $p1InfoTwo ], $table->getPropertyInfoForDataType( $p1TypeTwo ) );

		// And cached values that we expect to change have
		$this->assertCount( 1, $lookup->getAllPropertyInfo() );

		// But the class cache in the lookup continues to show old data
		// And the process cache via getWithSetCallback in WANCache also will hold the old data.
		$this->assertEquals( $p1Info, $lookup->getPropertyInfo( $p1 ) );
		$this->assertEquals( [ 'P1' => $p1Info ], $lookup->getAllPropertyInfo() );
		$this->assertEquals( [], $lookup->getPropertyInfoForDataType( $p1TypeTwo ) );
		$this->assertEquals( [ 'P1' => $p1Info ], $lookup->getPropertyInfoForDataType( $p1Type ) );

		[ $table, $cache, $store, $lookup ] = $this->getServices();

		// But with a new set of services, we can see everything persisted
		$this->assertCount( 1, $lookup->getAllPropertyInfo() );
		$this->assertEquals( $p1InfoTwo, $lookup->getPropertyInfo( $p1 ) );
		$this->assertEquals( [ 'P1' => $p1InfoTwo ], $lookup->getAllPropertyInfo() );
		$this->assertEquals( [], $lookup->getPropertyInfoForDataType( $p1Type ) );
		$this->assertEquals( [ 'P1' => $p1InfoTwo ], $lookup->getPropertyInfoForDataType( $p1TypeTwo ) );
	}

	public function testRemovingPropertyInfoPersistedAndNotCached() {
		/**
		 * @var PropertyInfoTable $table
		 * @var WANObjectCache $cache
		 * @var CacheAwarePropertyInfoStore $store
		 * @var CachingPropertyInfoLookup $lookup
		 */
		[ $table, $cache, $store, $lookup ] = $this->getServices();

		$p1 = new NumericPropertyId( 'P1' );
		$p1Info = $this->getInfo();
		$p1Type = $p1Info[PropertyInfoLookup::KEY_DATA_TYPE];
		$store->setPropertyInfo( $p1, $p1Info );
		$store->removePropertyInfo( $p1 );

		// The table should now have the property that we stored
		$this->assertSame( [], $table->getAllPropertyInfo() );
		$this->assertNull( $table->getPropertyInfo( $p1 ) );
		$this->assertEquals( [], $table->getAllPropertyInfo() );
		$this->assertEquals( [], $table->getPropertyInfoForDataType( $p1Type ) );

		// Using the caching store should not return the property info
		$this->assertSame( [], $lookup->getAllPropertyInfo() );
		$this->assertNull( $lookup->getPropertyInfo( $p1 ) );
		$this->assertEquals( [], $lookup->getAllPropertyInfo() );
		$this->assertEquals( [], $lookup->getPropertyInfoForDataType( $p1Type ) );
	}

	public function testServiceWithEmptyCacheRetrievesFromTable() {
		/**
		 * @var PropertyInfoTable $table
		 * @var WANObjectCache $cache
		 * @var CacheAwarePropertyInfoStore $store
		 * @var CachingPropertyInfoLookup $lookup
		 */
		[ $table, $cache, $store, $lookup ] = $this->getServices();

		// Store some data
		$p1 = new NumericPropertyId( 'P1' );
		$p1Info = $this->getInfo();
		$p1Type = $p1Info[PropertyInfoLookup::KEY_DATA_TYPE];
		$store->setPropertyInfo( $p1, $p1Info );

		[ $table, $cache, $store, $lookup ] = $this->getServices();

		// When using new services, the caching store should lookup from the table
		$this->assertCount( 1, $lookup->getAllPropertyInfo() );
		$this->assertEquals( $p1Info, $lookup->getPropertyInfo( $p1 ) );
		$this->assertEquals( [ 'P1' => $p1Info ], $lookup->getAllPropertyInfo() );
		$this->assertEquals( [ 'P1' => $p1Info ], $lookup->getPropertyInfoForDataType( $p1Type ) );
	}

	public function testServiceWithCacheDoesntHitTable() {
		/**
		 * @var PropertyInfoTable $table
		 * @var WANObjectCache $cache
		 * @var CacheAwarePropertyInfoStore $store
		 * @var CachingPropertyInfoLookup $lookup
		 */
		[ $table, $cache, $store, $lookup ] = $this->getServices();

		// Store some data
		$p1 = new NumericPropertyId( 'P1' );
		$p1Info = $this->getInfo();
		$p1Type = $p1Info[PropertyInfoLookup::KEY_DATA_TYPE];
		$store->setPropertyInfo( $p1, $p1Info );

		// Retrieve everything to make sure it is cached
		$this->assertCount( 1, $lookup->getAllPropertyInfo() );
		$this->assertEquals( $p1Info, $lookup->getPropertyInfo( $p1 ) );
		$this->assertEquals( [ 'P1' => $p1Info ], $lookup->getAllPropertyInfo() );
		$this->assertEquals( [ 'P1' => $p1Info ], $lookup->getPropertyInfoForDataType( $p1Type ) );

		// Remove the value directly on the table & check
		$table->removePropertyInfo( $p1 );
		$this->assertSame( [], $table->getAllPropertyInfo() );

		// Everything should be cached, so the lookup returns the same stuff
		$this->assertCount( 1, $lookup->getAllPropertyInfo() );
		$this->assertEquals( $p1Info, $lookup->getPropertyInfo( $p1 ) );
		$this->assertEquals( [ 'P1' => $p1Info ], $lookup->getAllPropertyInfo() );
		$this->assertEquals( [ 'P1' => $p1Info ], $lookup->getPropertyInfoForDataType( $p1Type ) );
	}

	public function testStoreAndLookupServiceAlignment(): void {
		// This is a regression test checking that info store and info lookup use the same cache setup.
		// It simulates a situation in which a property is added and accessed in one request, and then
		// another property is added and accessed in another request.
		// If for example the store only uses a CacheAwarePropertyInfoStore for the WANCache (which
		// happened in the past), then the lookup's local server cache doesn't get purged properly,
		// which results in a failure when looking up the newer property.

		$services = $this->getServiceContainer();
		$lookup = WikibaseRepo::getPropertyInfoLookup( $services );
		$store = WikibaseRepo::getStore( $services )->getPropertyInfoStore();
		$services->getMainWANObjectCache()->useInterimHoldOffCaching( false );

		$property1 = new NumericPropertyId( 'P123' );
		$info1 = [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ];
		$store->setPropertyInfo( $property1, $info1 );
		$lookup->getPropertyInfo( $property1 ); // this is necessary because it may populate the lookup's additional cache

		// recreate the two services, but keep the caches
		$services->resetServiceForTesting( 'WikibaseRepo.PropertyInfoLookup' );
		$services->resetServiceForTesting( 'WikibaseRepo.Store' );

		$lookupAfterReset = WikibaseRepo::getPropertyInfoLookup( $services );
		$storeAfterReset = WikibaseRepo::getStore( $services )->getPropertyInfoStore();

		$property2 = new NumericPropertyId( 'P666' );
		$info2 = [ PropertyInfoLookup::KEY_DATA_TYPE => 'wikibase-entityid' ];
		$storeAfterReset->setPropertyInfo( $property2, $info2 );

		$this->assertEquals( $info1, $lookupAfterReset->getPropertyInfo( $property1 ) );
		$this->assertEquals( $info2, $lookupAfterReset->getPropertyInfo( $property2 ) );
	}

}
