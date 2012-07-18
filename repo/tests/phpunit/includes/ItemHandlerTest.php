<?php

namespace Wikibase\Test;
use \Wikibase\ItemHandler as ItemHandler;
use \Wikibase\Item as Item;
use \Wikibase\ItemContent as ItemContent;

/**
 * Tests for the Wikibase\ItemHandler class.
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
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemHandlerTest extends \MediaWikiTestCase {
	
	/**
	 * Enter description here ...
	 * @var ItemHandler
	 */
	protected $ch;
	
	/**
	 * @var ItemContent
	 */
	protected $itemContent;
	
	/**
	 * This is to set up the environment
	 */
	public function setUp() {
  		parent::setUp();
		$this->ch = new ItemHandler();
	}
	
  	/**
	 * This is to tear down the environment
	 */
	public function tearDown() {
		parent::tearDown();
	}
	
	/**
	 * This is to make sure the unserializeContent method work approx as it should with the provided data
	 * @dataProvider provideBasicData
	 */
	public function testUnserializeContent( $input, array $labels, array $descriptions, array $languages = null ) {
		$this->itemContent = $this->ch->unserializeContent( $input, CONTENT_FORMAT_JSON );
		$this->assertInstanceOf(
			'\Wikibase\ItemContent',
			$this->itemContent,
			'Calling unserializeContent on a \Wikibase\ItemHandler should return a \Wikibase\Item'
		);
	}

	/**
	 * @dataProvider provideBasicData
	 * @depends testUnserializeContent
	 */
	public function testGetLabels( $input, array $labels, array $descriptions, array $languages = null ) {
		$this->itemContent = $this->ch->unserializeContent( $input, CONTENT_FORMAT_JSON );
		$this->assertEquals(
			$labels,
			$this->itemContent->getItem()->getLabels( $languages ),
			'Testing getLabels on a new \Wikibase\Item after creating it with preset values and doing a unserializeContent'
		);
	}

	/**
	 * @dataProvider provideBasicData
	 * @depends testUnserializeContent
	 */
	public function testGetDescriptions( $input, array $labels, array $descriptions, array $languages = null ) {
		$this->itemContent = $this->ch->unserializeContent( $input, CONTENT_FORMAT_JSON );
		$this->assertEquals(
			$descriptions,
			$this->itemContent->getItem()->getDescriptions( $languages ),
			'Testing getDescriptions on a new \Wikibase\Item after creating it with preset values and doing a unserializeContent'
		);
	}
	
	/**
	 * Tests @see WikibaseItem::copy
	 * @dataProvider provideBasicData
	 * @depends testUnserializeContent
	 */
	public function testCopy( $input ) {
		$this->itemContent = $this->ch->unserializeContent( $input, CONTENT_FORMAT_JSON );
		$copy = $this->itemContent->copy();
		$this->assertInstanceOf(
			'\Wikibase\ItemContent',
			$copy,
			'Calling copy on the return value of \Wikibase\Item::unserializeContent() should still return a new \Wikibase\Item object'
		);
		$this->assertEquals(
			$copy,
			$this->itemContent,
			'Calling copy() on an item built by unserializeContent should return a similar object'
		);
	}
	
	public function provideBasicData() {
		return array(
			array(
				'{
					"label": { },
					"description": { }
				}',
				array(),
				array(),
				null,
			),
			array(
				'{
					"label": { },
					"description": { }
				}',
				array(),
				array(),
				array(),
			),
			array(
				'{
					"label": { },
					"description": { }
				}',
				array(),
				array(),
				array( 'en', 'de' ),
			),
			array(
				'{
					"label": { },
					"description": { }
				}',
				array(),
				array(),
				array( 'en' ),
			),

			// FIXME: these tests have knowledge of the internal structure.
			// Should use ItemObject class to build stuff and use that for testing
			// Below code uses old internal structure and is broken
		);
	}

	/**
	 * Tests @see WikibaseItem::getIdForSiteLink
	 */
	public function testNotFound() {
		$this->assertFalse(
			$this->ch->getIdForSiteLink( 9999, "ThisDoesNotExistAndProbablyWillNeverExist" ),
			'Calling getIdForLinkSite( 42, "ThisDoesNotExistAndProbablyWillNeverExist" ) should return false'
		);
	}

	/**
	 * Tests @see WikibaseItem::getTitleForId
	 */
	public function testGetTitleForId() {
		$title = $this->ch->getTitleForId( 42 );
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
		$page = $this->ch->getWikiPageForId( 42 );
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

}