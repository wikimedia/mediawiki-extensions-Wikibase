<?php

namespace Wikibase\Test;

use Wikibase\ItemContent;
use Wikibase\ItemDeletionUpdate;
use Wikibase\StoreFactory;

/**
 * @covers Wikibase\ItemDeletionUpdate
 *
 * @since 0.1
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
class ItemDeletionUpdateTest extends \MediaWikiTestCase {
	//@todo: make this a baseclass to use with all types of entities.

	public function testConstruct() {
		$update = new ItemDeletionUpdate( ItemContent::newEmpty() );
		$this->assertInstanceOf( '\Wikibase\ItemDeletionUpdate', $update );
		$this->assertInstanceOf( '\Wikibase\EntityDeletionUpdate', $update );
		$this->assertInstanceOf( 'DataUpdate', $update );
	}

	public function itemProvider() {
		return array_map(
			function( ItemContent $itemContent ) {
				return array( $itemContent );
			},
			TestItemContents::getItems()
		);
	}

	/**
	 * @dataProvider itemProvider
	 * @param ItemContent $itemContent
	 */
	public function testDoUpdate( ItemContent $itemContent ) {
		$itemContent->save( '', null, EDIT_NEW );
		$update = new ItemDeletionUpdate( $itemContent );
		$update->doUpdate();

		$id = $itemContent->getItem()->getId()->getNumericId();

		$linkLookup = StoreFactory::getStore()->newSiteLinkCache();
		$this->assertEquals( 0, $linkLookup->countLinks( array( $id ) ) );
	}

}
