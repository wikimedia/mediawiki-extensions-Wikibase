<?php

namespace Wikibase\Test;
use Wikibase\LocalItem as LocalItem;
use Wikibase\LocalItemsTable as LocalItemsTable;
use Wikibase\Item as Item;
use Wikibase\ItemObject as ItemObject;

/**
 * Tests for the Wikibase\LocalItem class.
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
class LocalItemTest extends \ORMRowTest {

	/**
	 * @see ORMRowTest::getRowClass()
	 * @since 0.1
	 * @return string
	 */
	protected function getRowClass() {
		return '\Wikibase\LocalItem';
	}

	/**
	 * @see ORMRowTest::getTableInstance()
	 * @since 0.1
	 * @return \IORMTable
	 */
	protected function getTableInstance() {
		return \Wikibase\LocalItemsTable::singleton();
	}

	public function constructorTestProvider() {
		return array(
			array(
				array(
					'item_id' => 42,
					'page_title' => \Title::newMainPage()->getFullText(),
					'item_data' => ItemObject::newEmpty(),
				),
				true
			),
		);
	}

}
	