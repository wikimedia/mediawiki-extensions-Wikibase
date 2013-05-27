<?php

namespace Wikibase\Test;

use Wikibase\Item;

/**
 * Tests for the Wikibase\Item class.
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
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ItemNewFromArrayTest extends \PHPUnit_Framework_TestCase {

	//@todo: make this a baseclass to use with all types of entitites.

	/**
	 * Enter description here ...
	 * @var Item
	 */
	protected $item = null;
	
	/**
	 * This is to set up the environment
	 */
	protected function setUp() {
  		parent::setUp();
		$this->item = Item::newFromArray( array( 'entity' => 'q42' ) );
	}
	
  	/**
	 * This is to tear down the environment
	 */
	function tearDown() {
		parent::tearDown();
	}
	
	/**
	 * Tests @see WikibaseItem::newFromArray
	 */
	public function testNewFromArray() {
		$this->assertInstanceOf(
			'\Wikibase\Item',
			$this->item,
			'After creating a Item with an entity "q42" it should still be a WikibaseItem'
		);
		// TODO: Should it return false?
//		$this->assertFalse(
//			$this->item->isEmpty(),
//			'Calling isEmpty on a new Item after creating it with an entity "q42" should return false'
//		);
		$this->assertCount(
			0,
			$this->item->getLabels(),
			'Calling count on labels for a newly created Item should return zero'
		);
		$this->assertCount(
			0,
			$this->item->getdescriptions(),
			'Calling count on descriptions for a newly created Item should return zero'
		);
	}
	
}