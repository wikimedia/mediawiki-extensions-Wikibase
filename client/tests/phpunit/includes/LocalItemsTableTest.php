<?php

namespace Wikibase\Test;
use \Wikibase\LocalItemsTable as LocalItemsTable;

/**
 * Tests for the Wikibase\LocalItemsTable class.
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
class LocalItemsTableTest extends \MediaWikiTestCase {

	public function testSingleton() {
		$this->assertInstanceOf( 'Wikibase\LocalItemsTable', LocalItemsTable::singleton() );
		$this->assertTrue( LocalItemsTable::singleton() === LocalItemsTable::singleton() );
	}



}
