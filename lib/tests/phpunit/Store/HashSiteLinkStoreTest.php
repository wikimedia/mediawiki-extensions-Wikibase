<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\HashSiteLinkStore;

/**
 * @covers \Wikibase\Lib\Store\HashSiteLinkStore
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class HashSiteLinkStoreTest extends \PHPUnit\Framework\TestCase {

	public function testGetItemIdForLink() {
		$itemId = new ItemId( 'Q900' );

		$item = new Item( $itemId );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$siteLinkStore = new HashSiteLinkStore();
		$siteLinkStore->saveLinksOfItem( $item );

		$this->assertEquals( $itemId, $siteLinkStore->getItemIdForLink( 'enwiki', 'Foo' ) );
		$this->assertNull( $siteLinkStore->getItemIdForLink( 'xywiki', 'Foo' ) );
	}

	public function testGetEntityIdForLinkedTitle() {
		$itemId = new ItemId( 'Q900' );

		$item = new Item( $itemId );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$siteLinkStore = new HashSiteLinkStore();
		$siteLinkStore->saveLinksOfItem( $item );

		$this->assertEquals( $itemId, $siteLinkStore->getEntityIdForLinkedTitle( 'enwiki', 'Foo' ) );
		$this->assertNull( $siteLinkStore->getEntityIdForLinkedTitle( 'xywiki', 'Foo' ) );
	}

	public function provideGetLinks() {
		$cases = [];

		$item1 = new Item( new ItemId( 'Q1' ) );
		$item1->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );
		$item1->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Bar' );

		$item2 = new Item( new ItemId( 'Q2' ) );
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Bar' );
		$item2->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Xoo' );

		$items = [ $item1, $item2 ];

		// #0: all ---------
		$cases[] = [
			$items,
			null, // items
			null, // sites
			null, // pages
			[ // expected
				[ 'enwiki', 'Foo', 1 ],
				[ 'dewiki', 'Bar', 1 ],
				[ 'enwiki', 'Bar', 2 ],
				[ 'dewiki', 'Xoo', 2 ],
			],
		];

		// #1: mismatch ---------
		$cases[] = [
			$items,
			null, // items
			[ 'enwiki' ], // sites
			[ 'Xoo' ], // pages
			[], // expected
		];

		// #2: by item ---------
		$cases[] = [
			$items,
			[ 1 ], // items
			null, // sites
			null, // pages
			[ // expected
				[ 'enwiki', 'Foo', 1 ],
				[ 'dewiki', 'Bar', 1 ],
			],
		];

		// #3: by site ---------
		$cases[] = [
			$items,
			null, // items
			[ 'enwiki' ], // sites
			null, // pages
			[ // expected
				[ 'enwiki', 'Foo', 1 ],
				[ 'enwiki', 'Bar', 2 ],
			],
		];

		// #4: by page ---------
		$cases[] = [
			$items,
			null, // items
			null, // sites
			[ 'Bar' ], // pages
			[ // expected
				[ 'dewiki', 'Bar', 1 ],
				[ 'enwiki', 'Bar', 2 ],
			],
		];

		// #5: by site and page ---------
		$cases[] = [
			$items,
			null, // items
			[ 'dewiki' ], // sites
			[ 'Bar' ], // pages
			[ // expected
				[ 'dewiki', 'Bar', 1 ],
			],
		];

		// #6: empty condition
		$cases[] = [
			$items,
			[], // items
			null, // sites
			null, // pages
			[], // expected
		];

		return $cases;
	}

	/**
	 * @dataProvider provideGetLinks
	 */
	public function testGetLinks( array $items, ?array $itemIds, ?array $sites, ?array $pages, array $expectedLinks ) {
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
			[
				new SiteLink( 'dewiki', 'Xoo' ),
				new SiteLink( 'enwiki', 'Foo' ),
			],
			$siteLinkStore->getSiteLinksForItem( $item->getId() )
		);

		// check links of unknown id
		$this->assertSame( [], $siteLinkStore->getSiteLinksForItem( new ItemId( 'Q123' ) ) );
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

		$this->assertSame(
			[],
			$siteLinkStore->getSiteLinksForItem( $itemId ),
			'get by item id'
		);

		$this->assertNull(
			$siteLinkStore->getItemIdForSiteLink( $siteLink ),
			'get by site link'
		);
	}

}
