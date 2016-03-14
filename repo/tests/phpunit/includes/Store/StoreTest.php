<?php

namespace Wikibase\Test;

use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SqlStore;
use Wikibase\Store;
use Wikibase\Store\EntityIdLookup;

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
				$wikibaseRepo->getEntityContentDataCodec(),
				$wikibaseRepo->getEntityIdParser(),
				$this->getMock( EntityIdLookup::class ),
				$this->getMock( EntityTitleLookup::class )
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
	 * @param Store $store
	 */
	public function testNewSiteLinkStore( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\Lib\Store\SiteLinkLookup', $store->newSiteLinkStore() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testNewTermCache( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\TermIndex', $store->getTermIndex() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testGetLabelConflictFinder( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\Lib\Store\LabelConflictFinder', $store->getLabelConflictFinder() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testNewIdGenerator( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\IdGenerator', $store->newIdGenerator() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testGetChangeLookup( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\Lib\Store\ChangeLookup', $store->getChangeLookup() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testGetChangeStore( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\Repo\Store\ChangeStore', $store->getChangeStore() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetSiteLinkConflictLookup( Store $store ) {
		$this->assertInstanceOf(
			'\Wikibase\Repo\Store\SiteLinkConflictLookup',
			$store->getSiteLinkConflictLookup()
		);
	}

}
