<?php

namespace Wikibase\Test;

use Wikibase\IdGenerator;
use Wikibase\Lib\Store\EntityChangeLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\Store\ChangeStore;
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
 * @group WikibaseRepo
 * @group WikibaseStore
 * @group Database
 *
 * @group medium
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StoreTest extends \MediaWikiTestCase {

	public function instanceProvider() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$instances = array(
			new SqlStore(
				$wikibaseRepo->getEntityChangeFactory(),
				$wikibaseRepo->getEntityContentDataCodec(),
				$wikibaseRepo->getEntityIdParser(),
				$this->getMock( EntityIdLookup::class ),
				$this->getMock( EntityTitleLookup::class ),
				new EntityNamespaceLookup( [] )
			)
		);

		return array( $instances );
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
