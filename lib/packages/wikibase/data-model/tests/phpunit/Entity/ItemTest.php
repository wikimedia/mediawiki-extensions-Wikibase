<?php

namespace Wikibase\Test;

use Diff\Diff;
use Diff\DiffOpAdd;
use Diff\DiffOpChange;
use Diff\DiffOpRemove;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\ItemDiff;
use Wikibase\Property;
use Wikibase\PropertyNoValueSnak;
use Wikibase\Statement;

/**
 * @covers Wikibase\Item
 * Some tests for this class are located in ItemMultilangTextsTest,
 * ItemNewEmptyTest and ItemNewFromArrayTest.
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
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseItem
 * @group WikibaseDataModel
 * @group WikibaseItemTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ItemTest extends EntityTest {

	/**
	 * @see EntityTest::getNewEmpty
	 *
	 * @since 0.1
	 *
	 * @return \Wikibase\Item
	 */
	protected function getNewEmpty() {
		return Item::newEmpty();
	}

	/**
	 * @see   EntityTest::getNewFromArray
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return \Wikibase\Entity
	 */
	protected function getNewFromArray( array $data ) {
		return Item::newFromArray( $data );
	}

	public function testConstructor() {
		$instance = new Item( array() );

		$this->assertInstanceOf( 'Wikibase\Item', $instance );
	}

	public function testToArray() {
		/**
		 * @var \Wikibase\Item $item
		 */
		foreach ( TestItems::getItems() as $item ) {
			$this->assertInternalType( 'array', $item->toArray() );
		}
	}

	public function testGetId() {
		/**
		 * @var \Wikibase\Item $item
		 */
		foreach ( TestItems::getItems() as $item ) {
			// getId()
			$this->assertTrue( is_null( $item->getId() ) || $item->getId() instanceof EntityId );
		}
	}

	public function testSetId() {
		/**
		 * @var \Wikibase\Item $item
		 */
		foreach ( TestItems::getItems() as $item ) {
			$item->setId( 42 );
			$this->assertEquals( 42, $item->getId()->getNumericId() );
		}
	}

	public function testIsEmpty() {
		parent::testIsEmpty();

		$item = Item::newEmpty();
		$item->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Foobar' ) );

		$this->assertFalse( $item->isEmpty() );
	}

	public function testClear() {
		parent::testClear(); //NOTE: we must test the Item implementation of the functionality already tested for Entity.

		$item = $this->getNewEmpty();

		$item->addSimpleSiteLink( new SimpleSiteLink( "enwiki", "Foozzle" ) );

		$item->clear();

		$this->assertEmpty( $item->getSimpleSiteLinks(), "sitelinks" );
		$this->assertTrue( $item->isEmpty() );
	}

	public function itemProvider() {
		$items = array();

		$items[] = Item::newEmpty();

		$item = Item::newEmpty();
		$item->setDescription( 'en', 'foo' );
		$items[] = $item;

		$item = Item::newEmpty();
		$item->setDescription( 'en', 'foo' );
		$item->setDescription( 'de', 'foo' );
		$item->setLabel( 'en', 'foo' );
		$item->setAliases( 'de', array( 'bar', 'baz' ) );
		$items[] = $item;

		/**
		 * @var Item $item;
		 */
		$item = $item->copy();
		$item->addClaim( new Statement(
			new PropertyNoValueSnak( new EntityId( Property::ENTITY_TYPE, 42 ) )
		) );
		$items[] = $item;

		$argLists = array();

		foreach ( $items as $item ) {
			$argLists[] = array( $item );
		}

		return $argLists;
	}

	public function diffProvider() {
		$argLists = parent::diffProvider();

		// Addition of a sitelink
		$entity0 = $this->getNewEmpty();
		$entity1 = $this->getNewEmpty();
		$entity1->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Berlin' ) );

		$expected = new \Wikibase\EntityDiff( array(
			'links' => new Diff( array(
				'enwiki' => new DiffOpAdd( 'Berlin' ),
			), true ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );


		// Removal of a sitelink
		$entity0 = $this->getNewEmpty();
		$entity0->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Berlin' ) );
		$entity1 = $this->getNewEmpty();

		$expected = new \Wikibase\EntityDiff( array(
			'links' => new Diff( array(
				'enwiki' => new DiffOpRemove( 'Berlin' ),
			), true ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );


		// Modification of a sitelink
		$entity0 = $this->getNewEmpty();
		$entity0->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Berlin' ) );
		$entity1 = $this->getNewEmpty();
		$entity1->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Foobar' ) );

		$expected = new \Wikibase\EntityDiff( array(
			'links' => new Diff( array(
				'enwiki' => new DiffOpChange( 'Berlin', 'Foobar' ),
			), true ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );

		return $argLists;
	}

	public function patchProvider() {
		$argLists = parent::patchProvider();

		// Addition of a sitelink
		$source = $this->getNewEmpty();
		$patch = new ItemDiff( array(
			'links' => new Diff( array( 'enwiki' => new DiffOpAdd( 'Berlin' ) ), true )
		) );
		$expected = clone $source;
		$expected->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Berlin' ) );

		$argLists[] = array( $source, $patch, $expected );


		// Retaining of a sitelink
		$source = clone $expected;
		$patch = new ItemDiff();
		$expected = clone $source;

		$argLists[] = array( $source, $patch, $expected );


		// Modification of a sitelink
		$source = clone $expected;
		$patch = new ItemDiff( array(
			'links' => new Diff( array( 'enwiki' => new DiffOpChange( 'Berlin', 'Foobar' ) ), true )
		) );
		$expected = $this->getNewEmpty();
		$expected->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Foobar' ) );

		$argLists[] = array( $source, $patch, $expected );


		// Removal of a sitelink
		$source = clone $expected;
		$patch = new ItemDiff( array(
			'links' => new Diff( array( 'enwiki' => new DiffOpRemove( 'Foobar' ) ), true )
		) );
		$expected = $this->getNewEmpty();

		$argLists[] = array( $source, $patch, $expected );

		return $argLists;
	}

	public function testGetSimpleSiteLinkWithNonSetSiteId() {
		$item = Item::newEmpty();

		$this->setExpectedException( 'OutOfBoundsException' );
		$item->getSimpleSiteLink( 'enwiki' );
	}

	/**
	 * @dataProvider simpleSiteLinkProvider
	 */
	public function testAddSimpleSiteLink( SimpleSiteLink $siteLink ) {
		$item = Item::newEmpty();

		$item->addSimpleSiteLink( $siteLink );

		$this->assertEquals(
			$siteLink,
			$item->getSimpleSiteLink( $siteLink->getSiteId() )
		);
	}

	public function simpleSiteLinkProvider() {
		$argLists = array();

		$argLists[] = array( new SimpleSiteLink( 'enwiki', 'Wikidata' ) );
		$argLists[] = array( new SimpleSiteLink( 'nlwiki', 'Wikidata' ) );
		$argLists[] = array( new SimpleSiteLink( 'nlwiki', 'Nyan!' ) );
		$argLists[] = array( new SimpleSiteLink( 'foo bar', 'baz bah' ) );

		return $argLists;
	}

	/**
	 * @dataProvider simpleSiteLinksProvider
	 */
	public function testGetSimpleSiteLinks() {
		$siteLinks = func_get_args();
		$item = Item::newEmpty();

		foreach ( $siteLinks as $siteLink ) {
			$item->addSimpleSiteLink( $siteLink );
		}

		$this->assertInternalType( 'array', $item->getSimpleSiteLinks() );
		$this->assertEquals( $siteLinks, $item->getSimpleSiteLinks() );
	}

	public function simpleSiteLinksProvider() {
		$argLists = array();

		$argLists[] = array();

		$argLists[] = array( new SimpleSiteLink( 'enwiki', 'Wikidata' ) );

		$argLists[] = array(
			new SimpleSiteLink( 'enwiki', 'Wikidata' ),
			new SimpleSiteLink( 'nlwiki', 'Wikidata' )
		);

		$argLists[] = array(
			new SimpleSiteLink( 'enwiki', 'Wikidata' ),
			new SimpleSiteLink( 'nlwiki', 'Wikidata' ),
			new SimpleSiteLink( 'foo bar', 'baz bah' )
		);

		return $argLists;
	}

	public function testHasLinkToSiteForFalse() {
		$item = Item::newEmpty();
		$item->addSimpleSiteLink( new SimpleSiteLink( 'ENWIKI', 'Wikidata' ) );

		$this->assertFalse( $item->hasLinkToSite( 'enwiki' ) );
		$this->assertFalse( $item->hasLinkToSite( 'dewiki' ) );
		$this->assertFalse( $item->hasLinkToSite( 'foo bar' ) );
	}

	public function testHasLinkToSiteForTrue() {
		$item = Item::newEmpty();
		$item->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Wikidata' ) );
		$item->addSimpleSiteLink( new SimpleSiteLink( 'dewiki', 'Wikidata' ) );
		$item->addSimpleSiteLink( new SimpleSiteLink( 'foo bar', 'Wikidata' ) );

		$this->assertTrue( $item->hasLinkToSite( 'enwiki' ) );
		$this->assertTrue( $item->hasLinkToSite( 'dewiki' ) );
		$this->assertTrue( $item->hasLinkToSite( 'foo bar' ) );
	}

}
