<?php

/**
 * Tests for the WikibaseItem class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * 
 */
class WikibaseItemNewFromArrayTests extends MediaWikiTestCase {
	
	/**
	 * Enter description here ...
	 * @var WikibaseItem
	 */
	protected $item = null;
	
	/**
	 * This is to set up the environment
	 */
	protected function setUp() {
  		parent::setUp();
		$this->item = WikibaseItem::newFromArray( array( 'entity' => 'q42' ) );
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
			'WikibaseItem',
			$this->item,
			'After creating a WikibaseItem with an entity "q42" it should still be a WikibaseItem'
		);
		$this->assertFalse(
			$this->item->isNew(),
			'Calling isNew on a new WikibaseItem after creating it with an entity "q42" should return false'
		);
		$this->assertInstanceOf(
			'Title',
			$this->item->getTitle(),
			'Calling getTitle on a WikibaseItem after creating it with an entity "q42" should return a Title'
		);
		$this->assertRegExp(
			'/Q42/i',
			$this->item->getTitle()->getBaseText(),
			'Calling getTitle on a new WikibaseItem after creating it with an entity "q42" should return "q42"'
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
	
}