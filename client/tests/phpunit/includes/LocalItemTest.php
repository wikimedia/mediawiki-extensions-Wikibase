<?php

namespace Wikibase\Test;
use Wikibase\LocalItem as LocalItem;
use Wikibase\LocalItems as LocalItems;
use Wikibase\Item as Item;

/**
 * Tests for the Diff class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseLocalItem
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LocalItemTest extends \MediaWikiTestCase {

	public function constructorTestProvider() {
		return array(
			array(
				array(
					'item_id' => 42,
					'page_id' => 9001,
					'item_data' => Item::newEmpty(),
				),
				true
			),
		);
	}

	protected function verifyFields( LocalItem $item, array $data ) {
		foreach ( array_keys( $data ) as $fieldName ) {
			$this->assertEquals( $data[$fieldName], $item->getField( $fieldName ) );
		}

		$this->assertEquals( $data['item_data'], $item->getItem() );
	}

	/**
	 * @dataProvider constructorTestProvider
	 */
	public function testConstructor( array $data, $loadDefaults ) {
		$item = new LocalItem( LocalItems::singleton(), $data, $loadDefaults );

		$this->verifyFields( $item, $data );
	}

	/**
	 * @dataProvider constructorTestProvider
	 */
	public function testSave( array $data, $loadDefaults ) {
		$item = new LocalItem( LocalItems::singleton(), $data, $loadDefaults );

		$this->assertTrue( $item->save() );

		$this->assertTrue( $item->hasIdField() );
		$this->assertTrue( is_integer( $item->getId() ) );

		//$this->assertTrue( $item->getId() > 0 );

		$id = $item->getId();

		$this->assertTrue( $item->save() );

		$this->assertEquals( $id, $item->getId() );

		$this->verifyFields( $item, $data );
	}

	/**
	 * @dataProvider constructorTestProvider
	 */
	public function testRemove( array $data, $loadDefaults ) {
		$item = new LocalItem( LocalItems::singleton(), $data, $loadDefaults );

		$this->assertTrue( $item->save() );

		$this->assertTrue( $item->remove() );

		$this->assertFalse( $item->hasIdField() );

		$this->assertTrue( $item->save() );

		//$this->assertNotEquals( $id, $item->getId() );

		$this->verifyFields( $item, $data );

		$this->assertTrue( $item->remove() );

		$this->assertFalse( $item->hasIdField() );

		$this->verifyFields( $item, $data );
	}

}
	