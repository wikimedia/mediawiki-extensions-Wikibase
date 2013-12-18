<?php

namespace Wikibase\Test;

use Wikibase\SqlStore;
use Wikibase\Store;

/**
 * @covers Wikibase\Store
 *
 * @since 0.1
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StoreTest extends \MediaWikiTestCase {

	public function instanceProvider() {
		$instances = array( new SqlStore() );

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
		$this->assertInstanceOf( '\Wikibase\SiteLinkLookup', $store->newSiteLinkCache() );
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
