<?php

namespace Wikibase\Test;

use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Property;

/**
 * Tests for the MockRepository class.
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
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseEntityLookup
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MockRepositoryTest extends \MediaWikiTestCase {

	/* @var MockRepository */
	protected $repo;

	public function setUp() {
		parent::setUp();
		$this->repo = new MockRepository();
	}

	public function testHasEntity() {
		$q23 = new EntityId( Item::ENTITY_TYPE, 23 );
		$q42 = new EntityId( Item::ENTITY_TYPE, 42 );

		$p23 = new EntityId( Property::ENTITY_TYPE, 23 );
		$p42 = new EntityId( Property::ENTITY_TYPE, 42 );

		$item = Item::newEmpty();
		$item->setId( $q23 );
		$this->repo->putEntity( $item );

		$prop = Property::newEmpty();
		$prop->setId( $p23 );
		$this->repo->putEntity( $prop );

		// test item
		$this->assertTrue( $this->repo->hasEntity( $q23 ) );
		$this->assertFalse( $this->repo->hasEntity( $q42 ) );

		// test prop
		$this->assertTrue( $this->repo->hasEntity( $p23 ) );
		$this->assertFalse( $this->repo->hasEntity( $p42 ) );
	}

	public function testGetEntity() {
		$item = new Item( array() );
		$item->setLabel( 'en', 'foo' );

		// set up a data Item
		$this->repo->putEntity( $item, 23 );
		$itemId = $item->getId();

		// set up another version of the data Item
		$item->setLabel( 'de', 'bar' );
		$this->repo->putEntity( $item, 24 );

		// set up a property
		$prop = new Property( array() );
		$prop->setLabel( 'en', 'foo' );
		$prop->setId( $itemId->getNumericId() ); // same numeric id, different prefix

		$propId = $prop->getId();
		$this->repo->putEntity( $prop );

		// test latest item
		$item = $this->repo->getEntity( $itemId );
		$this->assertNotNull( $item, "Entity " . $itemId );
		$this->assertInstanceOf( '\Wikibase\Item', $item, "Entity " . $itemId );
		$this->assertEquals( 'foo', $item->getLabel( 'en' ) );
		$this->assertEquals( 'bar', $item->getLabel( 'de' ) );

		// test item by rev id
		$item = $this->repo->getEntity( $itemId, 23 );
		$this->assertNotNull( $item, "Entity " . $itemId . "@23" );
		$this->assertInstanceOf( '\Wikibase\Item', $item, "Entity " . $itemId );
		$this->assertEquals( 'foo', $item->getLabel( 'en' ) );
		$this->assertEquals( null, $item->getLabel( 'de' ) );

		// test latest prop
		$prop = $this->repo->getEntity( $propId );
		$this->assertNotNull( $prop, "Entity " . $propId );
		$this->assertInstanceOf( '\Wikibase\Property', $prop, "Entity " . $propId );
	}

	public function testGetEntityRevision() {
		$item = new Item( array() );
		$item->setLabel( 'en', 'foo' );

		// set up a data Item
		$this->repo->putEntity( $item, 23, "20130101000000" );
		$itemId = $item->getId();

		// set up another version of the data Item
		$item->setLabel( 'de', 'bar' );
		$this->repo->putEntity( $item, 24 );

		// set up a property
		$prop = new Property( array() );
		$prop->setLabel( 'en', 'foo' );
		$prop->setId( $itemId->getNumericId() ); // same numeric id, different prefix

		$propId = $prop->getId();
		$this->repo->putEntity( $prop );

		// test latest item
		$itemRev = $this->repo->getEntityRevision( $itemId );
		$this->assertNotNull( $item, "Entity " . $itemId );
		$this->assertInstanceOf( '\Wikibase\EntityRevision', $itemRev, "Entity " . $itemId );
		$this->assertInstanceOf( '\Wikibase\Item', $itemRev->getEntity(), "Entity " . $itemId );
		$this->assertEquals( 24, $itemRev->getRevision() );

		// test item by rev id
		$itemRev = $this->repo->getEntityRevision( $itemId, 23 );
		$this->assertNotNull( $item, "Entity " . $itemId . "@23" );
		$this->assertInstanceOf( '\Wikibase\EntityRevision', $itemRev, "Entity " . $itemId );
		$this->assertInstanceOf( '\Wikibase\Item', $itemRev->getEntity(), "Entity " . $itemId );
		$this->assertEquals( 23, $itemRev->getRevision() );
		$this->assertEquals( "20130101000000", $itemRev->getTimestamp() );

		// test latest prop
		$propRev = $this->repo->getEntityRevision( $propId );
		$this->assertNotNull( $propRev, "Entity " . $propId );
		$this->assertInstanceOf( '\Wikibase\EntityRevision', $propRev, "Entity " . $propId );
		$this->assertInstanceOf( '\Wikibase\Property', $propRev->getEntity(), "Entity " . $propId );
	}

	public function testGetItemIdForLink() {
		$item = new Item( array() );
		$item->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Foo' ) );

		// test item lookup
		$this->repo->putEntity( $item );
		$itemId = $item->getId();

		$this->assertEquals( $itemId->getNumericId(), $this->repo->getItemIdForLink( 'enwiki', 'Foo' ) );
		$this->assertEquals( false, $this->repo->getItemIdForLink( 'xywiki', 'Foo' ) );

		// test lookup after item modification
		$item->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Bar' ), 'set' );
		$this->repo->putEntity( $item );

		$this->assertEquals( false, $this->repo->getItemIdForLink( 'enwiki', 'Foo' ) );
		$this->assertEquals( $itemId->getNumericId(), $this->repo->getItemIdForLink( 'enwiki', 'Bar' ) );

		// test lookup after item deletion
		$this->repo->removeEntity( $itemId );

		$this->assertEquals( false, $this->repo->getItemIdForLink( 'enwiki', 'Foo' ) );
		$this->assertEquals( false, $this->repo->getItemIdForLink( 'enwiki', 'Bar' ) );
	}

	public static function provideGetConflictsForItem() {
		$cases = array();

		// #0: same link ---------
		$a = new Item( array( 'id' => 1 ) );
		$a->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Foo' ) );
		$a->addSimpleSiteLink( new SimpleSiteLink( 'dewiki', 'Foo' ) );

		$b = new Item( array( 'id' => 2 ) );
		$b->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Foo' ) );
		$b->addSimpleSiteLink( new SimpleSiteLink( 'dewiki', 'Bar' ) );

		$cases[] = array( $a, $b, array( array( 'enwiki', 'Foo', 1 ) ) );

		// #1: same site ---------
		$a = new Item( array( 'id' => 1 ) );
		$a->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Foo' ) );

		$b = new Item( array( 'id' => 2 ) );
		$b->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Bar' ) );

		$cases[] = array( $a, $b, array() );

		// #2: same page ---------
		$a = new Item( array( 'id' => 1 ) );
		$a->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Foo' ) );

		$b = new Item( array( 'id' => 2 ) );
		$b->addSimpleSiteLink( new SimpleSiteLink( 'dewiki', 'Foo' ) );

		$cases[] = array( $a, $b, array() );

		// #3: same item ---------
		$a = new Item( array( 'id' => 1 ) );
		$a->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Foo' ) );

		$cases[] = array( $a, $a, array() );

		return $cases;
	}

	/**
	 * @dataProvider provideGetConflictsForItem
	 */
	public function testGetConflictsForItem( Item $a, Item $b, $expectedConflicts ) {
		$this->repo->putEntity( $a );
		$conflicts = $this->repo->getConflictsForItem( $b );

		$this->assertArrayEquals( $expectedConflicts, $conflicts );
	}

	public static function provideGetLinks() {
		$cases = array();

		$a = new Item( array( 'id' => 1 ) );
		$a->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Foo' ) );
		$a->addSimpleSiteLink( new SimpleSiteLink( 'dewiki', 'Bar' ) );

		$b = new Item( array( 'id' => 2 ) );
		$b->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Bar' ) );
		$b->addSimpleSiteLink( new SimpleSiteLink( 'dewiki', 'Xoo' ) );

		$items = array( $a, $b );

		// #0: all ---------
		$cases[] = array( $items,
			array(), // items
			array(), // sites
			array(), // pages
			array(  // expected
				array( 'enwiki', 'Foo', 1 ),
				array( 'dewiki', 'Bar', 1 ),
				array( 'enwiki', 'Bar', 2 ),
				array( 'dewiki', 'Xoo', 2 ),
			)
		);

		// #1: mismatch ---------
		$cases[] = array( $items,
			array(), // items
			array( 'enwiki' ), // sites
			array( 'Xoo' ), // pages
			array() // expected
		);

		// #2: by item ---------
		$cases[] = array( $items,
			array( 1 ), // items
			array(), // sites
			array(), // pages
			array( // expected
				array( 'enwiki', 'Foo', 1 ),
				array( 'dewiki', 'Bar', 1 ),
			)
		);

		// #3: by site ---------
		$cases[] = array( $items,
			array(), // items
			array( 'enwiki' ), // sites
			array(), // pages
			array( // expected
				array( 'enwiki', 'Foo', 1 ),
				array( 'enwiki', 'Bar', 2 ),
			)
		);

		// #4: by page ---------
		$cases[] = array( $items,
			array(), // items
			array(), // sites
			array( 'Bar' ), // pages
			array( // expected
				array( 'dewiki', 'Bar', 1 ),
				array( 'enwiki', 'Bar', 2 ),
			)
		);

		// #5: by site and page ---------
		$cases[] = array( $items,
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
		foreach ( $items as $item ) {
			$this->repo->putEntity( $item );
		}

		$links = $this->repo->getLinks( $itemIds, $sites, $pages );

		$this->assertArrayEquals( $expectedLinks, $links );
	}

	/**
	 * @dataProvider provideGetLinks
	 */
	public function testCountLinks( array $items, array $itemIds, array $sites, array $pages, array $expectedLinks ) {
		foreach ( $items as $item ) {
			$this->repo->putEntity( $item );
		}

		$n = $this->repo->countLinks( $itemIds, $sites, $pages );

		$this->assertEquals( count( $expectedLinks ), $n );
	}

	public static function provideGetEntities() {
		return array(
			array( // #0: empty
				array(), // ids
				array(), // expected
			),

			array( // #1: some entities
				array( // ids
					'q1',
					'q2',
				),
				array( // expected
					'q1' => array(
						'de' => 'eins',
						'en' => 'one',
					),
					'q2' => array(
						'en' => 'two',
					),
				),
			),

			array( // #2: bad ID
				array( 'q1', 'q22' ), // ids
				array( // expected
					'q1' => array(
						'en' => 'one',
						'de' => 'eins',
					),
					'q22' => null,
				),
			)
		);
	}

	protected function setupGetEntities() {
		$one = new Item( array( 'id' => 1, 'label' => array( 'en' => 'one' ) ) );
		$two = new Item( array( 'id' => 2, 'label' => array( 'en' => 'two' ) ) );
		$three = new Item( array( 'id' => 3, 'label' => array( 'en' => 'three' ) ) );
		$prop = new Property( array( 'id' => 101, 'label' => array( 'en' => 'property!' ) ) );

		$this->repo->putEntity( $one, 1001 );
		$this->repo->putEntity( $two, 1002 );
		$this->repo->putEntity( $three, 1003 );
		$this->repo->putEntity( $prop, 1101 );

		$one->setLabel( 'de', "eins" );
		$this->repo->putEntity( $one, 1011 );
	}

	/**
	 * @dataProvider provideGetEntities
	 */
	public function testGetEntities( $ids, $expected, $expectedError = false ) {
		$this->setupGetEntities();

		// convert string IDs to EntityId objects
		foreach ( $ids as $i => $id ) {
			if ( is_string( $id ) ) {
				$ids[ $i ] = EntityId::newFromPrefixedId( $id );
			}
		}

		$entities = false;

		// do it!
		try {
			$entities = $this->repo->getEntities( $ids );

			if ( $expectedError !== false  ) {
				$this->fail( "expected error: " . $expectedError );
			}
		} catch ( \MWException $ex ) {
			if ( $expectedError !== false ) {
				$this->assertInstanceOf( $expectedError, $ex );
			} else {
				$this->fail( "error: " . $ex->getMessage() );
			}
		}

		if ( !is_array( $expected ) ) {
			// expected some kind of special return value, e.g. false.
			$this->assertEquals( $expected, $entities, "return value" );
			return;
		} else {
			$this->assertType( 'array', $entities, "return value" );
		}

		// extract map of entity IDs to label arrays.
		/* @var Entity $e  */
		$actual = array();
		foreach ( $entities as $key => $e ) {
			if ( is_object( $e ) ) {
				$actual[ $e->getId()->getPrefixedId() ] = $e->getLabels();
			} else {
				$actual[ $key ] = $e;
			}
		}

		// check that we found the right number of entities
		$this->assertEquals( count( $expected ), count( $actual ), "number of entities found" );

		foreach ( $expected as $id => $labels ) {
			// check that thew correct entity was found
			$this->assertArrayHasKey( $id, $actual );

			if ( is_array( $labels ) ) {
				// check that the entity contains the expected labels
				$this->assertArrayEquals( $labels, $actual[$id] );
			} else {
				// typically, $labels would be null here.
				// check that the entity/revision wasn't found
				$this->assertEquals( $labels, $actual[$id] );
			}
		}
	}

	public function testGetSiteLinksForItem() {
		$one = new Item( array( 'id' => 1 ) );
		$prop = new Property( array( 'id' => 101 ) );

		$one->addSimpleSiteLink( new SimpleSiteLink( 'dewiki', 'Xoo' ) );
		$one->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Foo' ) );

		$this->repo->putEntity( $one );
		$this->repo->putEntity( $prop );

		// check link retrieval
		$this->assertEquals(
			array(
				new SimpleSiteLink( 'dewiki', 'Xoo' ),
				new SimpleSiteLink( 'enwiki', 'Foo' ),
			),
			$this->repo->getSiteLinksForItem( $one->getId() )
		);

		// check links of unknown id
		$this->assertEmpty( $this->repo->getSiteLinksForItem( new EntityId( 'item', 123 ) ) );

		// check links if property
		$this->assertEmpty( $this->repo->getSiteLinksForItem( $prop->getId() ) );
	}

}
