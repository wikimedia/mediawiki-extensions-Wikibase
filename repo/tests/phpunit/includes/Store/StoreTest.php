<?php

namespace Wikibase\Repo\Tests\Store;

use MediaWikiIntegrationTestCase;
use ObjectCacheFactory;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\Lib\Changes\ChangeStore;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Repo\Hooks\GetEntityByLinkedTitleLookupHook;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\IdGenerator;
use Wikibase\Repo\Store\ItemsWithoutSitelinksFinder;
use Wikibase\Repo\Store\Sql\SqlStore;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Store\Sql\SqlStore
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StoreTest extends MediaWikiIntegrationTestCase {

	private Store $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = new SqlStore(
			WikibaseRepo::getEntityChangeFactory(),
			WikibaseRepo::getEntityIdParser(),
			WikibaseRepo::getEntityIdComposer(),
			$this->createMock( EntityIdLookup::class ),
			$this->createMock( EntityTitleStoreLookup::class ),
			new EntityNamespaceLookup( [] ),
			$this->createMock( IdGenerator::class ),
			$this->createMock( WikibaseServices::class ),
			$this->createMock( GetEntityByLinkedTitleLookupHook::class ),
			new DatabaseEntitySource( 'testsource', 'testdb', [], '', '', '', '' ),
			new SettingsArray( [
				'sharedCacheKeyPrefix' => 'wikibase_shared/testdb',
				'sharedCacheKeyGroup' => 'testdb',
				'sharedCacheType' => CACHE_NONE,
				'sharedCacheDuration' => 60 * 60,
			] ),
			WikibaseRepo::getPropertyInfoLookup(),
			$this->createMock( ObjectCacheFactory::class )
		);
	}

	public function testNewSiteLinkStore() {
		$this->assertInstanceOf( SiteLinkLookup::class, $this->store->newSiteLinkStore() );
	}

	public function testItemsWithoutSitelinksFinder() {
		$this->assertInstanceOf( ItemsWithoutSitelinksFinder::class, $this->store->newItemsWithoutSitelinksFinder() );
	}

	public function testGetEntityChangeLookup() {
		$this->assertInstanceOf( EntityChangeLookup::class, $this->store->getEntityChangeLookup() );
	}

	public function testGetChangeStore() {
		$this->assertInstanceOf( ChangeStore::class, $this->store->getChangeStore() );
	}

	public function testLookupCacheConstantsHaveDistinctValues() {
		$constants = [
			Store::LOOKUP_CACHING_ENABLED,
			Store::LOOKUP_CACHING_DISABLED,
			Store::LOOKUP_CACHING_RETRIEVE_ONLY,
		];
		$this->assertSameSize( $constants, array_unique( $constants ) );
	}

}
