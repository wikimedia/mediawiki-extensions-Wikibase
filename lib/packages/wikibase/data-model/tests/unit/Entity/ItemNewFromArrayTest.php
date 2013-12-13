<?php

namespace Wikibase\Test;

use Wikibase\Item;

/**
 * @covers Wikibase\Item
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