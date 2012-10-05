<?php

namespace Wikibase\Test;
use \Wikibase\ItemModificationUpdate as ItemModificationUpdate;
use \Wikibase\ItemContent as ItemContent;

/**
 *  Tests for the Wikibase\ItemModificationUpdate class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group DataUpdate
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemModificationUpdateTest extends \MediaWikiTestCase {

	public function testConstruct() {
		$update = new ItemModificationUpdate( ItemContent::newEmpty() );
		$this->assertInstanceOf( '\Wikibase\ItemModificationUpdate', $update );
		$this->assertInstanceOf( '\Wikibase\EntityModificationUpdate', $update );
		$this->assertInstanceOf( '\DataUpdate', $update );
	}

	public function itemProvider() {
		return array_map(
			function( ItemContent $itemContent ) { return array( $itemContent ); },
			\Wikibase\Test\TestItemContents::getEntities()
		);
	}

	/**
	 * @dataProvider itemProvider
	 * @param ItemContent $itemContent
	 */
	public function testDoUpdate( ItemContent $itemContent ) {
		\TestSites::insertIntoDb();

		$itemContent->save( '', null, EDIT_NEW );
		$update = new ItemModificationUpdate( $itemContent );
		$update->doUpdate();

		$item = $itemContent->getItem();
		$id = $item->getId();

		// TODO: use store

		$this->assertEquals(
			count( $item->getSiteLinks() ),
			$this->countRows( 'wb_items_per_site', array( 'ips_item_id' => $id ) )
		);

		// TODO: verify terms

		$update = new \Wikibase\ItemDeletionUpdate( $itemContent );
		$update->doUpdate();
	}

	protected function countRows( $table, array $conds = array() ) {
		return wfGetDB( DB_SLAVE )->selectRow(
			$table,
			array( 'COUNT(*) AS rowcount' ),
			$conds
		)->rowcount;
	}

}
