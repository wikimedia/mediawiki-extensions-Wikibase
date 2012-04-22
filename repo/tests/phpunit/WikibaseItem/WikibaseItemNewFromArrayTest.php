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
class WikibaseItemTests extends MediaWikiTestCase {
	
	/**
	 * Enter description here ...
	 * @var WikibaseItem
	 */
	protected $item = null;
	
	/**
	 * This is to set up the environment
	 */
	protected function setUp() {
		$this->item = WikibaseItem::newFromArray( array( 'entity' => 'q42' ) );
		fwrite(STDOUT, __METHOD__ . "\n");
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
		$this->assertTrue(
			$this->item->hasId(),
			'Calling hasID on a new WikibaseItem after creating it with an entity "q42" should return true'
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