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
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SqlStore;
use Wikibase\Store;
use Wikibase\Store\EntityIdLookup;
use Wikibase\TermIndex;

/**
 * @covers Wikibase\Store
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

	public function instanceProvider() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$instances = [
			new SqlStore(
				$wikibaseRepo->getEntityChangeFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getEntityIdComposer(),
				$this->getMock( EntityIdLookup::class ),
				$this->getMock( EntityTitleStoreLookup::class ),
				new EntityNamespaceLookup( [] ),
				$this->getMock( WikibaseServices::class )
			)
		];

		return [ $instances ];
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testRebuild( Store $store ) {
		$store->rebuild();
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testNewSiteLinkStore( Store $store ) {
		$this->assertInstanceOf( SiteLinkLookup::class, $store->newSiteLinkStore() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testNewEntitiesWithoutTermFinder( Store $store ) {
		$this->assertInstanceOf( EntitiesWithoutTermFinder::class, $store->newEntitiesWithoutTermFinder() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testItemsWithoutSitelinksFinder( Store $store ) {
		$this->assertInstanceOf( ItemsWithoutSitelinksFinder::class, $store->newItemsWithoutSitelinksFinder() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testNewTermCache( Store $store ) {
		$this->assertInstanceOf( TermIndex::class, $store->getTermIndex() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetLabelConflictFinder( Store $store ) {
		$this->assertInstanceOf( LabelConflictFinder::class, $store->getLabelConflictFinder() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testNewIdGenerator( Store $store ) {
		$this->assertInstanceOf( IdGenerator::class, $store->newIdGenerator() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetEntityChangeLookup( Store $store ) {
		$this->assertInstanceOf( EntityChangeLookup::class, $store->getEntityChangeLookup() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetChangeStore( Store $store ) {
		$this->assertInstanceOf( ChangeStore::class, $store->getChangeStore() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetSiteLinkConflictLookup( Store $store ) {
		$this->assertInstanceOf(
			SiteLinkConflictLookup::class,
			$store->getSiteLinkConflictLookup()
		);
	}

}
