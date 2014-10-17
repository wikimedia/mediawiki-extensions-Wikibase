<?php

namespace Wikibase\Test;

use Wikibase\Repo\WikibaseRepo;
use Wikibase\SqlStore;
use Wikibase\Store;

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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StoreTest extends \MediaWikiTestCase {

	public function instanceProvider() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$instances = array(
			new SqlStore(
				$wikibaseRepo->getEntityContentDataCodec(),
				$wikibaseRepo->getEntityIdParser()
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
	public function testNewSiteLinkCache( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\Lib\Store\SiteLinkLookup', $store->newSiteLinkCache() );
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
	public function testNewIdGenerator( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\IdGenerator', $store->newIdGenerator() );
	}

}
