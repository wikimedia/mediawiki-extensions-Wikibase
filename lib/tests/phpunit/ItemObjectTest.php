<?php

namespace Wikibase\Test;
use \Wikibase\ItemObject as ItemObject;
use \Wikibase\Item as Item;

/**
 * Tests for the Wikibase\ItemObject class.
 * Some tests for this class are located in ItemMultilangTextsTest,
 * ItemNewEmptyTest and ItemNewFromArrayTest.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseItem
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ItemObjectTest extends \MediaWikiTestCase {

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
			$this->assertTrue( is_null( $item->getId() ) || is_integer( $item->getId() ) );
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

	public function labelProvider() {
		return array(
			array( 'en', 'spam' ),
			array( 'en', 'spam', 'spam' ),
			array( 'de', 'foo bar baz' ),
		);
	}

	/**
	 * @dataProvider labelProvider
	 * @param string $languageCode
	 * @param string $labelText
	 * @param string $moarText
	 */
	public function testSetLabel( $languageCode, $labelText, $moarText = 'ohi there' ) {
		$item = ItemObject::newEmpty();

		$item->setLabel( $languageCode, $labelText );

		$this->assertEquals( $labelText, $item->getLabel( $languageCode ) );

		$item->setLabel( $languageCode, $moarText );

		$this->assertEquals( $moarText, $item->getLabel( $languageCode ) );
	}

	/**
	 * @dataProvider labelProvider
	 * @param string $languageCode
	 * @param string $labelText
	 * @param string $moarText
	 */
	public function testGetLabel( $languageCode, $labelText, $moarText = 'ohi there' ) {
		$item = ItemObject::newEmpty();

		$this->assertFalse( $item->getLabel( $languageCode ) );

		$item->setLabel( $languageCode, $labelText );

		$this->assertEquals( $labelText, $item->getLabel( $languageCode ) );
	}

	public function testGetSiteLinks() {
		/**
		 * @var \Wikibase\Item $item
		 */
		foreach ( TestItems::getItems() as $item ) {
			$this->assertInternalType( 'array', $item->getSiteLinks() );
		}
	}

	// TODO: We're not testing a lot here are we now? o_O

}
	