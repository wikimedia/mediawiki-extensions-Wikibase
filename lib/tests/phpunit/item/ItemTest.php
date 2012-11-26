<?php

namespace Wikibase\Test;
use Wikibase\Item;
use Wikibase\SiteLink;

/**
 * Tests for the Wikibase\Item class.
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
 * @group WikibaseLib
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

		$exception = null;
		try {
			new Item( 'Exception throws you!' );
		} catch ( \Exception $exception )
		{}
		$this->assertInstanceOf( '\Exception', $exception );
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
			$this->assertTrue( is_null( $item->getId() ) || $item->getId() instanceof \Wikibase\EntityId );
			// getPrefixedId()
			$this->assertTrue(
				$item->getId() === null ? $item->getPrefixedId() === null : is_string( $item->getPrefixedId() )
			);
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

	public function testGetSiteLinks() {
		/**
		 * @var \Wikibase\Item $item
		 */
		foreach ( TestItems::getItems() as $item ) {
			$links = $item->getSiteLinks();
			$this->assertInternalType( 'array', $links );

			foreach ( $links as $link ) {
				$this->assertInstanceOf( '\Wikibase\SiteLink', $link );
			}
		}
	}

	public function testIsEmpty() {
		parent::testIsEmpty();

		$item = Item::newEmpty();
		$item->addSiteLink( SiteLink::newFromText( 'enwiki', 'Foobar' ) );

		$this->assertFalse( $item->isEmpty() );
	}

	public function testClear() {
		parent::testClear(); //NOTE: we must test the Item implementation of the functionality already tested for Entity.

		$item = $this->getNewEmpty();

		$item->addSiteLink( SiteLink::newFromText( "enwiki", "Foozzle" ) );

		$item->clear();

		$this->assertEmpty( $item->getSiteLinks(), "sitelinks" );
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
		$item->addClaim( new \Wikibase\StatementObject(
			new \Wikibase\PropertyNoValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 42 ) )
		) );
		$items[] = $item;

		return $this->arrayWrap( $items );
	}

}
