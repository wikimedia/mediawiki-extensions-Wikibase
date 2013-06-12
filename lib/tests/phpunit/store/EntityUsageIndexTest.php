<?php

namespace Wikibase\Test;
use MediaWikiSite;
use Site;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Item;
use Wikibase\EntityUsageIndex;
use Wikibase\EntityId;
use Wikibase\Property;

/**
 * Test class for EntityUsageIndex
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityUsageIndexTest extends \MediaWikiTestCase {

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
			$item->addSimpleSiteLink( new SimpleSiteLink( $siteId, $page ) );
		}

		return $item;
	}

	/**
	 * @param Item[]      $items
	 * @param Site        $site
	 *
	 * @return EntityUsageIndex
	 */
	protected function newEntityUsageIndex( array $items, Site $site ) {
		$repo = new MockRepository();

		foreach ( $items as $item ) {
			$repo->putEntity( $item );
		}

		$index = new EntityUsageIndex( $site, $repo );
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
					new EntityId( Item::ENTITY_TYPE, 1 )
				),
				array( // expectedUsage
					'Foo'
				)
			),

			array( // #1
				$items,
				$barWiki,
				array( // wantedEntities
					new EntityId( Item::ENTITY_TYPE, 1 )
				),
				array( // expectedUsage
					'Bar'
				)
			),

			array( // #2
				$items,
				$fooWiki,
				array( // wantedEntities
					new EntityId( Item::ENTITY_TYPE, 2 )
				),
				array( // expectedUsage
					'Too'
				)
			),

			array( // #3
				$items,
				$barWiki,
				array( // wantedEntities
					new EntityId( Item::ENTITY_TYPE, 2 )
				),
				array( // expectedUsage
				)
			),

			array( // #4
				$items,
				$fooWiki,
				array( // wantedEntities
					new EntityId( Item::ENTITY_TYPE, 1 ),
					new EntityId( Item::ENTITY_TYPE, 2 ),
				),
				array( // expectedUsage
					'Foo', 'Too'
				)
			),

			array( // #5
				$items,
				$barWiki,
				array( // wantedEntities
					new EntityId( Item::ENTITY_TYPE, 1 ),
					new EntityId( Item::ENTITY_TYPE, 2 ),
				),
				array( // expectedUsage
					'Bar'
				)
			),

			array( // #6
				$items,
				$fooWiki,
				array( // wantedEntities
					new EntityId( Item::ENTITY_TYPE, 1 ),
					new EntityId( Item::ENTITY_TYPE, 1 ),
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
	 *
	 */
	public function testGetEntityUsage( array $repoItems,
		Site $site, $wantedEntities, $expectedUsage ) {

		$index = $this->newEntityUsageIndex( $repoItems, $site );
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
					new EntityId( Item::ENTITY_TYPE, 1 )
				)
			),

			array( // #1
				$items,
				$barWiki,
				array( // wantedPages
					'Bar'
				),
				array( // expectedUsed
					new EntityId( Item::ENTITY_TYPE, 1 )
				)
			),

			array( // #2
				$items,
				$fooWiki,
				array( // wantedPages
					'Too'
				),
				array( // expectedUsed
					new EntityId( Item::ENTITY_TYPE, 2 )
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
					new EntityId( Item::ENTITY_TYPE, 1 ),
					new EntityId( Item::ENTITY_TYPE, 2 ),
				)
			),

			array( // #5
				$items,
				$barWiki,
				array( // wantedPages
					'Bar', 'Tar'
				),
				array( // expectedUsed
					new EntityId( Item::ENTITY_TYPE, 1 ),
				)
			),

			array( // #6
				$items,
				$fooWiki,
				array( // wantedPages
					'Foo', 'Foo'
				),
				array( // expectedUsed
					new EntityId( Item::ENTITY_TYPE, 1 ),
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
	 *
	 */
	public function testGetUsedEntities( array $repoItems,
		Site $site, $wantedPages, $expectedUsed ) {

		$index = $this->newEntityUsageIndex( $repoItems, $site );
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
					new EntityId( Item::ENTITY_TYPE, 1 )
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
					new EntityId( Item::ENTITY_TYPE, 1 )
				)
			),

			array( // #1
				$items,
				$barWiki,
				array( // wantedEntities
					new EntityId( Item::ENTITY_TYPE, 1 )
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
					new EntityId( Item::ENTITY_TYPE, 1 )
				)
			),

			array( // #2
				$items,
				$fooWiki,
				array( // wantedEntities
					new EntityId( Item::ENTITY_TYPE, 2 )
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
					new EntityId( Item::ENTITY_TYPE, 2 )
				)
			),

			array( // #3
				$items,
				$barWiki,
				array( // wantedEntities
					new EntityId( Item::ENTITY_TYPE, 2 )
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
				)
			),

			array( // #4
				$items,
				$fooWiki,
				array( // wantedEntities
					new EntityId( Item::ENTITY_TYPE, 1 ),
					new EntityId( Item::ENTITY_TYPE, 2 ),
					new EntityId( Item::ENTITY_TYPE, 3 ),
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
					new EntityId( Item::ENTITY_TYPE, 1 ),
					new EntityId( Item::ENTITY_TYPE, 2 ),
				)
			),

			array( // #5
				$items,
				$barWiki,
				array( // wantedEntities
					new EntityId( Item::ENTITY_TYPE, 1 ),
					new EntityId( Item::ENTITY_TYPE, 2 ),
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
					new EntityId( Item::ENTITY_TYPE, 1 ),
				)
			),

			array( // #6
				$items,
				$fooWiki,
				array( // wantedEntities
					new EntityId( Item::ENTITY_TYPE, 1 ),
					new EntityId( Item::ENTITY_TYPE, 1 ),
				),
				Item::ENTITY_TYPE,
				array( // expectedUsage
					new EntityId( Item::ENTITY_TYPE, 1 ),
					new EntityId( Item::ENTITY_TYPE, 1 ), //TODO: do we want to remove dupes here too?!
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
	 *
	 */
	public function testFilterUnusedEntities( array $repoItems,
		Site $site, $wantedEntities, $wantedType, $expectedUsed ) {

		$index = $this->newEntityUsageIndex( $repoItems, $site );
		$used = $index->filterUnusedEntities( $wantedEntities, $wantedType );

		$this->assertArrayEquals( $expectedUsed, $used );
	}
}
