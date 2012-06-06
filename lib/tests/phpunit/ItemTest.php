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
class ItemTest extends \MediaWikiTestCase {

	/**
	 * This is to set up the environment
	 */
	public function setUp() {
  		parent::setUp();
	}
	
  	/**
	 * This is to tear down the environment
	 */
	public function tearDown() {
		parent::tearDown();
	}
	
	/**
	 * Tests @see WikibaseItem::getIdForSiteLink
	 */
	public function testNotFound() {
		$this->assertFalse(
			Item::getIdForSiteLink( 9999, "ThisDoesNotExistAndProbablyWillNeverExist" ),
			'Calling getIdForLinkSite( 42, "ThisDoesNotExistAndProbablyWillNeverExist" ) should return false'
		);
	}
	
	/**
	 * Tests @see WikibaseItem::getTitleForId
	 */
	public function testGetTitleForId() {
		$title = Item::getTitleForId( 42 );
		$this->assertInstanceOf(
			'\Title',
			$title,
			'Calling WikibaseItem::getTitleForId(42) should return a Title object'
		);
		$this->assertRegExp(
			'/Q42/i',
			$title->getBaseText(),
			'Calling getBaseText() on returned Title from WikibaseItem::getTitleForId(42), ie either a new item with this id or an existing, should return number 42'
		);
	}
	
	/**
	 * Tests @see WikibaseItem::getWikiPageForId
	 */
	public function testGetWikiPageForId() {
		$page = Item::getWikiPageForId( 42 );
		$this->assertInstanceOf(
			'\WikiPage',
			$page,
			'Calling WikibaseItem::getWikiPageForId(42) should return a WikiPage object'
		);
		$this->assertRegExp(
			'/Q42/i',
			$page->getTitle()->getBaseText(),
			'Calling getTitle()->getBaseText() on returned WikiPage from WikibaseItem::getTitleForId(42), ie either a new item with this id or an existing, should return number 42'
		);
	}
		
	 	// TODO: This is set to assertFalse, which is not correct, its done because isEmpty is not fully implemented
		/*
		$this->assertFalse(
			$item->isEmpty(),
			'Calling isEmpty on a new empty WikibaseItem should return true'
		);
		*/
		
	/**
	 * Tests @see WikibaseItem::getPropertyNames
	 * @Stub
	 */
	public function testGetPropertyNames() {
		$this->markTestSkipped(
			'The getPropertyNames is not implemented yet.'
		);
	}

	/**
	 * Tests @see WikibaseItem::getSystemPropertyNames
	 * @Stub
	 */
	public function testGetSystemPropertyNames() {
		$this->markTestSkipped(
			'The getSystemPropertyNames is not implemented yet.'
		);
	}

	/**
	 * Tests @see WikibaseItem::getEditorialPropertyNames
	 * @Stub
	 */
	public function testGetEditorialPropertyNames() {
		$this->markTestSkipped(
			'The getEditorialPropertyNames is not implemented yet.'
		);
	}

	/**
	 * Tests @see WikibaseItem::getStatementPropertyNames
	 * @Stub
	 */
	public function testGetStatementPropertyNames() {
		$this->markTestSkipped(
			'The getStatementPropertyNames is not implemented yet.'
		);
	}

	/**
	 * Tests @see WikibaseItem::getPropertyMultilang
	 * @Stub
	 */
	public function testGetPropertyMultilang() {
		$this->markTestSkipped(
			'The getPropertyMultilang is not implemented yet.'
		);
	}

	/**
	 * Tests @see WikibaseItem::getProperty
	 * @Stub
	 */
	public function testGetProperty() {
		$this->markTestSkipped(
			'The getProperty is not implemented yet.'
		);
	}

	/**
	 * Tests @see WikibaseItem::getPropertyType
	 * @Stub
	 */
	public function testGetPropertyType() {
		$this->markTestSkipped(
			'The getPropertyType is not implemented yet.'
		);
	}

	/**
	 * Tests @see WikibaseItem::isStatementProperty
	 * @Stub
	 */
	public function testIsStatementProperty() {
		$this->markTestSkipped(
			'The isStatementProperty is not implemented yet.'
		);
	}

	/**
	 * Tests @see WikibaseItem::getTextForSearchIndex
	 * @Stub
	 */
	public function testGetTextForSearchIndex() {
		$this->markTestSkipped(
			'The isStatementProperty is not implemented yet.'
		);
	}

}
	