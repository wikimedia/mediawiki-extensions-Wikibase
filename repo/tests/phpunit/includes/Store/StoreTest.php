<?php

namespace Wikibase\Repo\Tests\Store;

use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\Lib\Changes\ChangeStore;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\IdGenerator;
use Wikibase\Repo\Store\ItemsWithoutSitelinksFinder;
use Wikibase\Repo\Store\SiteLinkConflictLookup;
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

	public function instanceProvider() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$instances = [
			new SqlStore(
				$wikibaseRepo->getEntityChangeFactory(),
				WikibaseRepo::getEntityIdParser(),
				$wikibaseRepo->getEntityIdComposer(),
				$this->createMock( EntityIdLookup::class ),
				$this->createMock( EntityTitleStoreLookup::class ),
				new EntityNamespaceLookup( [] ),
				$this->createMock( IdGenerator::class ),
				$this->createMock( WikibaseServices::class ),
				new EntitySource( 'testsource', 'testdb', [], '', '', '', '' )
			)
		];

		return [ $instances ];
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
	public function testItemsWithoutSitelinksFinder( Store $store ) {
		$this->assertInstanceOf( ItemsWithoutSitelinksFinder::class, $store->newItemsWithoutSitelinksFinder() );
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

	public function testLookupCacheConstantsHaveDistinctValues() {
		$constants = [
			Store::LOOKUP_CACHING_ENABLED,
			Store::LOOKUP_CACHING_DISABLED,
			Store::LOOKUP_CACHING_RETRIEVE_ONLY
		];
		$this->assertSame( count( $constants ), count( array_unique( $constants ) ) );
	}

}
