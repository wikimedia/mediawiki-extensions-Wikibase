<?php

namespace Wikibase\Lib\Tests\Store;

use HashBagOStuff;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionCache;

/**
 * @covers Wikibase\Lib\Store\EntityRevisionCache
 *
 * @group WikibaseEntityLookup
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class EntityRevisionCacheTest extends PHPUnit_Framework_TestCase {

	public function testGet() {
		$q5 = new ItemId( 'Q5' );
		$q2 = new ItemId( 'Q2' );

		$entityRevision = new EntityRevision( new Item( $q5 ) );

		$bagOStuff = new HashBagOStuff();
		$bagOStuff->set( 'blah:Q5', $entityRevision );

		$cache = new EntityRevisionCache( $bagOStuff, 3600, 'blah' );

		$this->assertNull( $cache->get( $q2 ), 'Cache miss' );
		$this->assertSame( $entityRevision, $cache->get( $q5 ), 'Cache hit' );
	}

	public function testSet() {
		$q5 = new ItemId( 'Q5' );

		$entityRevision = new EntityRevision( new Item( $q5 ) );

		$bagOStuff = new HashBagOStuff();

		$cache = new EntityRevisionCache( $bagOStuff, 3600, 'cache-key' );

		$this->assertNull( $cache->get( $q5 ), 'Cache miss' );

		$cache->set( $entityRevision );

		$this->assertSame( $entityRevision, $bagOStuff->get( 'cache-key:Q5' ) );
		$this->assertSame( $entityRevision, $cache->get( $q5 ) );
	}

	public function testDelete() {
		$q5 = new ItemId( 'Q5' );

		$entityRevision = new EntityRevision( new Item( $q5 ) );

		$bagOStuff = new HashBagOStuff();

		$cache = new EntityRevisionCache( $bagOStuff, 3600, 'cache-key' );
		$cache->set( $entityRevision );

		$this->assertSame( $entityRevision, $cache->get( $q5 ), 'Cache hit' );

		$cache->delete( $q5 );

		$this->assertFalse( $bagOStuff->get( 'cache-key:Q5' ), 'No longer cached' );
		$this->assertNull( $cache->get( $q5 ), 'No longer cached' );
	}

}
