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
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ItemMultilangTextsTest extends \PHPUnit_Framework_TestCase {

	//@todo: make this a baseclass to use with all types of entitites.
	
	/**
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
	 * Tests @see Item::setLabel
	 * Tests @see Item::getLabels
	 *
	 * @dataProvider providerLabels
	 */
	public function testLabels( $lang, $str ) {
		$label = self::$item->setLabel( $lang, $str);
		
		$this->assertEquals(
			$str,
			$label,
			"Did not get back whats stored from setLabel('{$lang}', '{$label}')"
		);
		
		$labels = self::$item->getLabels( array($lang) );
		
		$this->assertEquals(
			$str,
			$labels[$lang],
			"Did not get back whats stored from getLabels(array('{$lang}'))"
		);
	}
	
	public static function providerLabels() {
		return array(
			array( 'de', 'Bar' ),
			array( 'en', 'Foo' ),
		);
	}
	
	/**
	 * Tests @see Item::setDescription
	 * Tests @see Item::getDescriptions
	 *
	 * @dataProvider providerDescriptions
	 */
	public function testDescriptions( $lang, $str ) {
		$description = self::$item->setDescription( $lang, $str);
		
		$this->assertEquals(
			$str,
			$description,
			"Did not get back whats stored from setDescription('{$lang}', '{$description}')"
		);
		
		$descriptions = self::$item->getDescriptions( array($lang) );
		
		$this->assertEquals(
			$str,
			$descriptions[$lang],
			"Did not get back whats stored from getDescriptions(array('{$lang}'))"
		);
	}
	
	public static function providerDescriptions() {
		return array(
			array( 'de', 'This is about Bar' ),
			array( 'en', 'This is about Foo' ),
		);
	}
	
}