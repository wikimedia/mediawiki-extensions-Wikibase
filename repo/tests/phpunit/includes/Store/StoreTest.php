<?php

namespace Wikibase\Repo\Tests\Store;

use Wikibase\DataAccess\WikibaseServices;
use Wikibase\IdGenerator;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\Store\ChangeStore;
use Wikibase\Repo\Store\EntitiesWithoutTermFinder;
use Wikibase\Repo\Store\ItemsWithoutSitelinksFinder;
use Wikibase\Repo\Store\SiteLinkConflictLookup;
use Wikibase\Repo\Tests\WikibaseRepoAccess;
use Wikibase\SqlStore;
use Wikibase\Store;
use Wikibase\Store\EntityIdLookup;
use Wikibase\TermIndex;

/**
 * @covers \Wikibase\Store
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
class StoreTest extends \MediaWikiTestCase {

	use WikibaseRepoAccess;

	public function newSqlStore() {
		$wikibaseRepo = $this->getWikibaseRepo();

		return new SqlStore(
			$wikibaseRepo->getEntityChangeFactory(),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getEntityIdComposer(),
			$this->getMock( EntityIdLookup::class ),
			$this->getMock( EntityTitleStoreLookup::class ),
			new EntityNamespaceLookup( [] ),
			$this->getMock( WikibaseServices::class )
		);
	}

	public function testRebuild() {
		$store = $this->newSqlStore();
		$store->rebuild();
		$this->assertTrue( true );
	}

	public function testNewSiteLinkStore() {
		$store = $this->newSqlStore();
		$this->assertInstanceOf( SiteLinkLookup::class, $store->newSiteLinkStore() );
	}

	public function testNewEntitiesWithoutTermFinder() {
		$store = $this->newSqlStore();
		$this->assertInstanceOf( EntitiesWithoutTermFinder::class, $store->newEntitiesWithoutTermFinder() );
	}

	public function testItemsWithoutSitelinksFinder() {
		$store = $this->newSqlStore();
		$this->assertInstanceOf( ItemsWithoutSitelinksFinder::class, $store->newItemsWithoutSitelinksFinder() );
	}

	public function testNewTermCache() {
		$store = $this->newSqlStore();
		$this->assertInstanceOf( TermIndex::class, $store->getTermIndex() );
	}

	public function testGetLabelConflictFinder() {
		$store = $this->newSqlStore();
		$this->assertInstanceOf( LabelConflictFinder::class, $store->getLabelConflictFinder() );
	}

	public function testNewIdGenerator() {
		$store = $this->newSqlStore();
		$this->assertInstanceOf( IdGenerator::class, $store->newIdGenerator() );
	}

	public function testGetEntityChangeLookup() {
		$store = $this->newSqlStore();
		$this->assertInstanceOf( EntityChangeLookup::class, $store->getEntityChangeLookup() );
	}

	public function testGetChangeStore() {
		$store = $this->newSqlStore();
		$this->assertInstanceOf( ChangeStore::class, $store->getChangeStore() );
	}

	public function testGetSiteLinkConflictLookup() {
		$store = $this->newSqlStore();
		$this->assertInstanceOf(
			SiteLinkConflictLookup::class,
			$store->getSiteLinkConflictLookup()
		);
	}

	public function testLookupCacheConstantsHaveDistinctValues() {
		$constants = [
			Store::LOOKUP_CACHING_ENABLED,
			Store::LOOKUP_CACHING_DISABLED,
			Store::LOOKUP_CACHING_RETRIEVE_ONLY
		];
		$this->assertSame( count( $constants ), count( array_unique( $constants ) ) );
	}

}
