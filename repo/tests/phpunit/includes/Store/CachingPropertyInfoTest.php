<?php

namespace Wikibase\Repo\Tests\Store;

use HashBagOStuff;
use MediaWikiIntegrationTestCase;
use WANObjectCache;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Store\CacheAwarePropertyInfoStore;
use Wikibase\Lib\Store\CachingPropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;

/**
 * Integration tests for both CacheAwarePropertyInfoStore and CachingPropertyInfoLookup.
 * These are hard to test separately as as one is needed for observations about the other,
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

	protected function setUp(): void {
		parent::setUp();
		$this->tablesUsed[] = 'wb_property_info';
		$this->truncateTable( 'wb_property_info' );
	}

	private function newPropertyInfoTable() {
		return new PropertyInfoTable(
			$this->getEntityComposer(),
			$this->getRepoDomainDb(),
			true
		);
	}

	private function getEntityComposer() {
		return new EntityIdComposer( [
			Property::ENTITY_TYPE => function( $repository, $uniquePart ) {
				return NumericPropertyId::newFromRepositoryAndNumber( $repository, $uniquePart );
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
		list( $table, $cache, $store, $lookup ) = $this->getServices();

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
		list( $table, $cache, $store, $lookup ) = $this->getServices();

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

		list( $table, $cache, $store, $lookup ) = $this->getServices();

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
		list( $table, $cache, $store, $lookup ) = $this->getServices();

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
		list( $table, $cache, $store, $lookup ) = $this->getServices();

		// Store some data
		$p1 = new NumericPropertyId( 'P1' );
		$p1Info = $this->getInfo();
		$p1Type = $p1Info[PropertyInfoLookup::KEY_DATA_TYPE];
		$store->setPropertyInfo( $p1, $p1Info );

		list( $table, $cache, $store, $lookup ) = $this->getServices();

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
		list( $table, $cache, $store, $lookup ) = $this->getServices();

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

}
