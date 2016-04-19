<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\HashSiteLinkStore;

/**
 * @covers Wikibase\Lib\Store\HashSiteLinkStore
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class HashSiteLinkStoreTest extends \PHPUnit_Framework_TestCase {

	public function testGetItemIdForLink() {
		$itemId = new ItemId( 'Q900' );

		$item = new Item( $itemId );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$siteLinkStore = new HashSiteLinkStore();
		$siteLinkStore->saveLinksOfItem( $item );

		$this->assertEquals( $itemId, $siteLinkStore->getItemIdForLink( 'enwiki', 'Foo' ) );
		$this->assertNull( $siteLinkStore->getItemIdForLink( 'xywiki', 'Foo' ) );
	}

	public function provideGetLinks() {
		$cases = array();

		$item1 = new Item( new ItemId( 'Q1' ) );
		$item1->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );
		$item1->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Bar' );

		$item2 = new Item( new ItemId( 'Q2' ) );
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Bar' );
		$item2->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Xoo' );

		$items = array( $item1, $item2 );

		// #0: all ---------
		$cases[] = array(
			$items,
			array(), // items
			array(), // sites
			array(), // pages
			array( // expected
				array( 'enwiki', 'Foo', 1 ),
				array( 'dewiki', 'Bar', 1 ),
				array( 'enwiki', 'Bar', 2 ),
				array( 'dewiki', 'Xoo', 2 ),
			)
		);

		// #1: mismatch ---------
		$cases[] = array(
			$items,
			array(), // items
			array( 'enwiki' ), // sites
			array( 'Xoo' ), // pages
			array() // expected
		);

		// #2: by item ---------
		$cases[] = array(
			$items,
			array( 1 ), // items
			array(), // sites
			array(), // pages
			array( // expected
				array( 'enwiki', 'Foo', 1 ),
				array( 'dewiki', 'Bar', 1 ),
			)
		);

		// #3: by site ---------
		$cases[] = array(
			$items,
			array(), // items
			array( 'enwiki' ), // sites
			array(), // pages
			array( // expected
				array( 'enwiki', 'Foo', 1 ),
				array( 'enwiki', 'Bar', 2 ),
			)
		);

		// #4: by page ---------
		$cases[] = array(
			$items,
			array(), // items
			array(), // sites
			array( 'Bar' ), // pages
			array( // expected
				array( 'dewiki', 'Bar', 1 ),
				array( 'enwiki', 'Bar', 2 ),
			)
		);

		// #5: by site and page ---------
		$cases[] = array(
			$items,
			array(), // items
			array( 'dewiki' ), // sites
			array( 'Bar' ), // pages
			array( // expected
				array( 'dewiki', 'Bar', 1 ),
			)
		);

		return $cases;
	}

	/**
	 * @dataProvider provideGetLinks
	 */
	public function testGetLinks( array $items, array $itemIds, array $sites, array $pages, array $expectedLinks ) {
		$siteLinkStore = new HashSiteLinkStore();

		foreach ( $items as $item ) {
			$siteLinkStore->saveLinksOfItem( $item );
		}

		$this->assertEquals( $expectedLinks, $siteLinkStore->getLinks( $itemIds, $sites, $pages ) );
	}

	public function testGetSiteLinksForItem() {
		$item = new Item( new ItemId( 'Q1' ) );

		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Xoo' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$siteLinkStore = new HashSiteLinkStore();
		$siteLinkStore->saveLinksOfItem( $item );

		// check link retrieval
		$this->assertEquals(
			array(
				new SiteLink( 'dewiki', 'Xoo' ),
				new SiteLink( 'enwiki', 'Foo' ),
			),
			$siteLinkStore->getSiteLinksForItem( $item->getId() )
		);

		// check links of unknown id
		$this->assertEmpty( $siteLinkStore->getSiteLinksForItem( new ItemId( 'Q123' ) ) );
	}

	public function testGetItemIdForSiteLink() {
		$itemId = new ItemId( 'Q11' );
		$siteLink = new SiteLink( 'eswiki', 'Cerveza' );

		$item = new Item( $itemId );
		$item->getSiteLinkList()->addSiteLink( $siteLink );

		$siteLinkStore = new HashSiteLinkStore();
		$siteLinkStore->saveLinksOfItem( $item );

		$this->assertEquals( $itemId, $siteLinkStore->getItemIdForSiteLink( $siteLink ) );
	}

	public function testDeleteLinksOfItem() {
		$itemId = new ItemId( 'Q111' );
		$siteLink = new SiteLink( 'eswiki', 'Gato' );

		$item = new Item( $itemId );
		$item->getSiteLinkList()->addSiteLink( $siteLink );

		$siteLinkStore = new HashSiteLinkStore();
		$siteLinkStore->saveLinksOfItem( $item );

		$this->assertEquals( $itemId, $siteLinkStore->getItemIdForSiteLink( $siteLink ) );

		$siteLinkStore->deleteLinksOfItem( $itemId );

		$this->assertEmpty(
			$siteLinkStore->getSiteLinksForItem( $itemId ),
			'get by item id'
		);

		$this->assertNull(
			$siteLinkStore->getItemIdForSiteLink( $siteLink ),
			'get by site link'
		);
	}

}
