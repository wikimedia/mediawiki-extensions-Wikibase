<?php

namespace Wikibase\Test;
use \Wikibase\ItemObject as ItemObject;
use \Wikibase\Item as Item;
use \Wikibase\SiteLink as SiteLink;

/**
 * Tests for the Wikibase\ItemObject class.
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
 * @group WikibaseItemObjectTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ItemObjectTest extends EntityObjectTest {

	/**
	 * @see EntityObjectTest::getNewEmpty
	 *
	 * @since 0.1
	 *
	 * @return \Wikibase\Item
	 */
	protected function getNewEmpty() {
		return ItemObject::newEmpty();
	}

	/**
	 * @see   EntityObjectTest::getNewFromArray
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return \Wikibase\Entity
	 */
	protected function getNewFromArray( array $data ) {
		return ItemObject::newFromArray( $data );
	}

	public function testConstructor() {
		$instance = new ItemObject( array() );

		$this->assertInstanceOf( 'Wikibase\Item', $instance );

		$exception = null;
		try { $instance = new ItemObject( 'Exception throws you!' ); } catch ( \Exception $exception ){}
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
			$this->assertTrue( is_null( $item->getId() ) || is_integer( $item->getId() ) );
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
			$this->assertEquals( 42, $item->getId() );
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

		$item = ItemObject::newEmpty();
		$item->addSiteLink( SiteLink::newFromText( 'enwiki', 'Foobar' ) );

		$this->assertFalse( $item->isEmpty() );
	}

	public function testCopy() {
		$foo = ItemObject::newEmpty();
		$bar = $foo->copy();

		$this->assertInstanceOf( '\Wikibase\Item', $bar );
		$this->assertEquals( $foo, $bar );
		$this->assertFalse( $foo === $bar );
	}


	public function testClear() {
		parent::testClear(); //NOTE: we must test the ItemObject implementation of the functionality already tested for EntityObject.

		$item = $this->getNewEmpty();

		$item->addSiteLink( SiteLink::newFromText( "enwiki", "Foozzle" ) );

		$item->clear();

		$this->assertEmpty( $item->getSiteLinks(), "sitelinks" );
		$this->assertTrue( $item->isEmpty() );
	}

	public function itemProvider() {
		$items = array();

		$items[] = ItemObject::newEmpty();

		$item = ItemObject::newEmpty();
		$item->setDescription( 'en', 'foo' );
		$items[] = $item;

		$item = ItemObject::newEmpty();
		$item->setDescription( 'en', 'foo' );
		$item->setDescription( 'de', 'foo' );
		$item->setLabel( 'en', 'foo' );
		$item->setAliases( 'de', array( 'bar', 'baz' ) );
		$items[] = $item;

		$item = $item->copy();
		$item->addStatement( new \Wikibase\StatementObject( new \Wikibase\ClaimObject( new \Wikibase\PropertyNoValueSnak( 42 ) ) ) );
		$items[] = $item;

		return $this->arrayWrap( $items );
	}

	/**
	 * @dataProvider itemProvider
	 *
	 * @param Item $item
	 */
	public function testHasStatements( Item $item ) {
		$has = $item->hasStatements();
		$this->assertInternalType( 'boolean', $has );

		$this->assertEquals( count( $item->getStatements() ) !== 0, $has );
	}

}
