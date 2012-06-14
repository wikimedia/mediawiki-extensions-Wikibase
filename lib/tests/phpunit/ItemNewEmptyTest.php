<?php

namespace Wikibase\Test;
use \Wikibase\Item as Item;

/**
 * Tests for the WikibaseItem class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseItem
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ItemNewEmptyTest extends \MediaWikiTestCase {
	
	/**
	 * Enter description here ...
	 * @var Item
	 */
	protected $item;
	
	/**
	 * This is to set up the environment
	 */
	protected function setUp() {
  		parent::setUp();
		$this->item = Item::newEmpty();
	}
	
  	/**
	 * This is to tear down the environment
	 */
	function tearDown() {
		parent::tearDown();
	}
	
	/**
	 * Tests @see WikibaseItem::newEmpty
	 */
	public function testNewEmpty() {
		$this->assertInstanceOf(
			'\Wikibase\Item',
			$this->item,
			'After creating an empty WikibaseItem it should be a WikibaseItem'
		);
		$this->assertTrue(
			$this->item->isNew(),
			'Calling isNew on a new empty WikibaseItem after creating it should return true'
		);
		$this->assertEquals(
			null,
			$this->item->getId(),
			'Calling getId on a newly created WikibaseItem should return null'
		);
		$this->assertCount(
			0,
			$this->item->getLabels(),
			'Calling count on labels for a newly created WikibaseItem should return zero'
		);
		$this->assertCount(
			0,
			$this->item->getdescriptions(),
			'Calling count on descriptions for a newly created WikibaseItem should return zero'
		);
	}
	
	/**
	 * Tests @see WikibaseItem::getLabel
	 * Tests @see WikibaseItem::setLabel
	 * Tests @see WikibaseItem::getLabels
	 */
	public function testSetGetLabel() {
		$arr = array(
			'no' => 'Norge',
			'nn' => 'Noreg'
		);
		foreach ($arr as $key => $val) {
			$this->item->setLabel( $key, $val );
		}
		foreach ($arr as $key => $val) {
			$this->assertEquals(
				$val,
				$this->item->getLabel( $key, $val ),
				'Testing setLabel-getLabel pair with "{$key} => {$val}" a new empty WikibaseItem after creating the item'
			);
		}
		$this->assertEquals(
			$arr,
			$this->item->getLabels(),
			'Testing if getLabels reconstructs the whole structure after it is built with setLabel'
		);
	}
	
	/**
	 * Tests @see WikibaseItem::getDescription
	 * Tests @see WikibaseItem::setDescription
	 * Tests @see WikibaseItem::getDescriptions
	 */
	public function testSetGetDescription() {
		$arr = array(
			'no' => 'Norge mitt eget land',
			'nn' => 'Noreg mitt eige land'
		);
		foreach ($arr as $key => $val) {
			$this->item->setDescription( $key, $val );
		}
		foreach ($arr as $key => $val) {
			$this->assertEquals(
				$val,
				$this->item->getDescription( $key ),
				'Testing setDescription-getDescription pair with "{$key} => {$val}" a new empty WikibaseItem after creating the item'
			);
		}
		$this->assertEquals(
			$arr,
			$this->item->getDescriptions(),
			'Testing if getDescriptions reconstructs the whole structure after it is built with setDescription'
		);
	}
	
	/**
	 * Tests @see WikibaseItem::addSiteLink
	 * Tests @see WikibaseItem::removeSiteLink
	 * Tests @see WikibaseItem::getSiteLinks
	 */
	public function testAddRemoveSiteLink() {
		$arr = array(
			'no' => 'Norge',
			'nn' => 'Noreg'
		);
		foreach ($arr as $key => $val) {
			$this->item->addSiteLink( $key, $val );
		}
		$this->assertEquals(
			$arr,
			$this->item->getSiteLinks(),
			'Testing if getSiteLinks reconstructs the whole structure after it is built with addSiteLink'
		);
		foreach ($arr as $key => $val) {
			$this->item->removeSiteLink( $key, $val );
		}
		$this->assertCount(
			0,
			$this->item->getSiteLinks(),
			'Testing if removeSiteLink decrements the whole structure to zero after it is built with addSiteLink'
		);
	}
	
	public function testGetWikitextForTransclusion() {
		$this->assertFalse(
			$this->item->getWikitextForTransclusion(),
			'The getWikitextForTransclusion is not implemented yet.'
		);
	}
	
}
