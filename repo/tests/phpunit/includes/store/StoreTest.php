<?php

namespace Wikibase\Test;
use Wikibase\Store as Store;

/**
 * Tests for the Wikibase\Store implementing classes.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StoreTest extends \MediaWikiTestCase {

	public function instanceProvider() {
		$instances = array( new \Wikibase\SqlStore() );

		return $this->arrayWrap( $instances );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testSingleton( Store $store ) {
		$class = get_class( $store );
		$this->assertTrue( $class::singleton() === $class::singleton() );
		$this->assertInstanceOf( $class, $class::singleton() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testNewEntityDeletionHandler( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\EntityDeletionHandler', $store->newEntityDeletionHandler() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testNewEntityUpdateHandler( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\EntityUpdateHandler', $store->newEntityUpdateHandler() );
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
	public function testNewSiteLinkLookup( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\SiteLinkLookup', $store->newSiteLinkLookup() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testNewTermLookup( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\TermLookup', $store->newTermLookup() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testNewIdGenerator( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\IdGenerator', $store->newIdGenerator() );
	}

}
