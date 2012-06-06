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
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ItemMultilangTextsTest extends \MediaWikiTestCase {
	
	/**
	 * Enter description here ...
	 * @var Item
	 */
	protected static $item = null;
	
	/**
	 * This is to set up the environment
	 */
	protected function setUp() {
  		parent::setUp();
		self::$item = Item::newFromArray( array( 'entity' => 'q42' ) );
	}
	
  	/**
	 * This is to tear down the environment
	 */
	function tearDown() {
		parent::tearDown();
	}
	
	/**
	 * Tests @see WikibaseItem::setLabel
	 * Tests @see WikibaseItem::getLabels
	 * @dataProvider providerLabels
	 */
	public function testLabels( $lang, $str ) {
		
		$label = self::$item->setLabel( $lang, $str);
		
		$this->assertEquals(
			$str,
			$label['value'],
			"Did not get back whats stored from setLabel('{$lang}', '{$label}')"
		);
		
		$labels = self::$item->getLabels( array($lang) );
		
		$this->assertEquals(
			$str,
			$labels[$lang],
			"Did not get back whats stored from getLabels(array('{$lang}'))"
		);
		
		$rawlabels = self::$item->getRawLabels( array($lang) );
		
		$this->assertEquals(
			$str,
			$rawlabels[$lang]['value'],
			"Did not get back whats stored from getRawLabels(array('{$lang}'))"
		);
		
	}
	
	public function providerLabels() {
		return array(
			array('de', 'Bar'),
			array('en', 'Foo'),
		);
	}
	
	/**
	 * Tests @see WikibaseItem::setDescription
	 * Tests @see WikibaseItem::getDescriptions
	 * @dataProvider providerDescriptions
	 */
	public function testDescriptions( $lang, $str ) {
		
		$description = self::$item->setDescription( $lang, $str);
		
		$this->assertEquals(
			$str,
			$description['value'],
			"Did not get back whats stored from setDescription('{$lang}', '{$description}')"
		);
		
		$descriptions = self::$item->getDescriptions( array($lang) );
		
		$this->assertEquals(
			$str,
			$descriptions[$lang],
			"Did not get back whats stored from getDescriptions(array('{$lang}'))"
		);
		
		$rawdescriptions = self::$item->getRawDescriptions( array($lang) );
		
		$this->assertEquals(
			$str,
			$rawdescriptions[$lang]['value'],
			"Did not get back whats stored from getRawDescriptions(array('{$lang}'))"
		);
		
	}
	
	public function providerDescriptions() {
		return array(
			array('de', 'This is about Bar'),
			array('en', 'This is about Foo'),
		);
	}
	
}