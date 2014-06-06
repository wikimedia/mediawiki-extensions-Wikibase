<?php

namespace Wikibase\Test;

use MediaWikiSite;
use Site;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Entity\Item;
use Wikibase\ItemUsageIndex;

/**
 * @covers Wikibase\ItemUsageIndex
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ItemUsageIndexTest extends \MediaWikiTestCase {

	/**
	 * @param int $id
	 * @param array $links associative array of site => page.
	 *
	 * @return Item
	 */
	protected static function newItemFromLinks( $id, array $links ) {
		$item = Item::newEmpty();
		$item->setId( $id );

		foreach ( $links as $siteId => $page ) {
			$item->addSiteLink( new SiteLink( $siteId, $page ) );
		}

		return $item;
	}

	/**
	 * @param Item[] $items
	 * @param Site $site
	 *
	 * @return ItemUsageIndex
	 */
	protected function newItemUsageIndex( array $items, Site $site ) {
		$repo = new MockRepository();

		foreach ( $items as $item ) {
			$repo->putEntity( $item );
		}

		$index = new ItemUsageIndex( $site, $repo );
		return $index;
	}

	protected static function getTestSites() {
		static $sites = array();

		if ( !empty( $sites ) ) {
			return $sites;
		}

		$ids = array( "foo", "bar" );

		foreach ( $ids as $id ) {
			$site = new MediaWikiSite();
			$site->setGlobalId( $id );
			$sites[$id] = $site;
		}

		return $sites;
	}

	protected static function getTestItems() {
		static $items = array();

		if ( !empty( $items ) ) {
			return $items;
		}

		$items[] = self::newItemFromLinks( 1,
			array(
				'foo' => 'Foo',
				'bar' => 'Bar',
			)
		) ;

		$items[] = self::newItemFromLinks( 2,
			array(
				'foo' => 'Too',
			)
		) ;

		return $items;
	}

	public static function provideGetEntityUsage() {
		$sites = self::getTestSites();

		$fooWiki = $sites['foo'];
		$barWiki = $sites['bar'];

		$items = self::getTestItems();

		return array(
			array( // #0
				$items,
				$fooWiki,
				array( // wantedEntities
					new ItemId( 'q1' )
				),
				array( // expectedUsage
					'Foo'
				)
			),

			array( // #1
				$items,
				$barWiki,
				array( // wantedEntities
					new ItemId( 'q1' )
				),
				array( // expectedUsage
					'Bar'
				)
			),

			array( // #2
				$items,
				$fooWiki,
				array( // wantedEntities
					new ItemId( 'q2' )
				),
				array( // expectedUsage
					'Too'
				)
			),

			array( // #3
				$items,
				$barWiki,
				array( // wantedEntities
					new ItemId( 'q2' )
				),
				array( // expectedUsage
				)
			),

			array( // #4
				$items,
				$fooWiki,
				array( // wantedEntities
					new ItemId( 'q1' ),
					new ItemId( 'q2' ),
				),
				array( // expectedUsage
					'Foo', 'Too'
				)
			),

			array( // #5
				$items,
				$barWiki,
				array( // wantedEntities
					new ItemId( 'q1' ),
					new ItemId( 'q2' ),
				),
				array( // expectedUsage
					'Bar'
				)
			),

			array( // #6
				$items,
				$fooWiki,
				array( // wantedEntities
					new ItemId( 'q1' ),
					new ItemId( 'q1' ),
				),
				array( // expectedUsage
					'Foo'
				)
			),

			array( // #7
				$items,
				$barWiki,
				array( // wantedEntities
				),
				array( // expectedUsage
				)
			),
		);
	}

	/**
	 * @dataProvider provideGetEntityUsage
	 */
	public function testGetEntityUsage( array $repoItems,
		Site $site, $wantedEntities, $expectedUsage ) {

		$index = $this->newItemUsageIndex( $repoItems, $site );
		$usage = $index->getEntityUsage( $wantedEntities );

		$this->assertArrayEquals( $expectedUsage, $usage );
	}

	public static function provideGetUsedEntities() {
		$sites = self::getTestSites();
		$fooWiki = $sites['foo'];
		$barWiki = $sites['bar'];

		$items = self::getTestItems();

		return array(
			array( // #0
				$items,
				$fooWiki,
				array( // wantedPages
					'Foo'
				),
				array( // expectedUsed
					new ItemId( 'q1' )
				)
			),

			array( // #1
				$items,
				$barWiki,
				array( // wantedPages
					'Bar'
				),
				array( // expectedUsed
					new ItemId( 'q1' )
				)
			),

			array( // #2
				$items,
				$fooWiki,
				array( // wantedPages
					'Too'
				),
				array( // expectedUsed
					new ItemId( 'q2' )
				)
			),

			array( // #3
				$items,
				$barWiki,
				array( // wantedPages
					'Xoo'
				),
				array( // expectedUsed
				)
			),

			array( // #4
				$items,
				$fooWiki,
				array( // wantedPages
					'Foo', 'Too'
				),
				array( // expectedUsed
					new ItemId( 'q1' ),
					new ItemId( 'q2' ),
				)
			),

			array( // #5
				$items,
				$barWiki,
				array( // wantedPages
					'Bar', 'Tar'
				),
				array( // expectedUsed
					new ItemId( 'q1' ),
				)
			),

			array( // #6
				$items,
				$fooWiki,
				array( // wantedPages
					'Foo', 'Foo'
				),
				array( // expectedUsed
					new ItemId( 'q1' ),
				)
			),

			array( // #7
				$items,
				$barWiki,
				array( // wantedPages
				),
				array( // expectedUsed
				)
			),
		);
	}

	/**
	 * @dataProvider provideGetUsedEntities
	 */
	public function testGetUsedEntities( array $repoItems,
		Site $site, $wantedPages, $expectedUsed ) {

		$index = $this->newItemUsageIndex( $repoItems, $site );
		$used = $index->getUsedEntities( $wantedPages );

		$this->assertArrayEquals( $expectedUsed, $used );
	}


	public static function provideFilterUnusedEntities() {
		$sites = self::getTestSites();
		$fooWiki = $sites['foo'];
		$barWiki = $sites['bar'];

		$items = self::getTestItems();

		return array(
			array( // #0
				$items,
				$fooWiki,
				array( // wantedEntities
					new ItemId( 'q1' )
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
					new ItemId( 'q1' )
				)
			),

			array( // #1
				$items,
				$barWiki,
				array( // wantedEntities
					new ItemId( 'q1' )
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
					new ItemId( 'q1' )
				)
			),

			array( // #2
				$items,
				$fooWiki,
				array( // wantedEntities
					new ItemId( 'q2' )
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
					new ItemId( 'q2' )
				)
			),

			array( // #3
				$items,
				$barWiki,
				array( // wantedEntities
					new ItemId( 'q2' )
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
				)
			),

			array( // #4
				$items,
				$fooWiki,
				array( // wantedEntities
					new ItemId( 'q1' ),
					new ItemId( 'q2' ),
					new ItemId( 'q3' ),
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
					new ItemId( 'q1' ),
					new ItemId( 'q2' ),
				)
			),

			array( // #5
				$items,
				$barWiki,
				array( // wantedEntities
					new ItemId( 'q1' ),
					new ItemId( 'q2' ),
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
					new ItemId( 'q1' ),
				)
			),

			array( // #6
				$items,
				$fooWiki,
				array( // wantedEntities
					new ItemId( 'q1' ),
					new ItemId( 'q1' ),
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
					new ItemId( 'q1' ),
					new ItemId( 'q1' ), //TODO: do we want to remove dupes here too?!
				)
			),

			array( // #7
				$items,
				$barWiki,
				array( // wantedEntities
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
				)
			),
		);
	}

	/**
	 * @dataProvider provideFilterUnusedEntities
	 */
	public function testFilterUnusedEntities( array $repoItems,
		Site $site, $wantedEntities, $wantedType, $expectedUsed ) {

		$index = $this->newItemUsageIndex( $repoItems, $site );
		$used = $index->filterUnusedEntities( $wantedEntities, $wantedType );

		$this->assertArrayEquals( $expectedUsed, $used );
	}

}
