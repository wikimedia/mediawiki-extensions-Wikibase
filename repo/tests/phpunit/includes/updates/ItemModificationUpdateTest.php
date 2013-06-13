<?php

namespace Wikibase\Test;
use \Wikibase\ItemModificationUpdate;
use \Wikibase\ItemContent;

/**
 *  Tests for the Wikibase\ItemModificationUpdate class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group DataUpdate
 * @group ItemModificationUpdateTest
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
	//@todo: make this a baseclass to use with all types of entities.

	public function testConstruct() {
		$update = new ItemModificationUpdate( ItemContent::newEmpty() );
		$this->assertInstanceOf( '\Wikibase\ItemModificationUpdate', $update );
		$this->assertInstanceOf( '\Wikibase\EntityModificationUpdate', $update );
		$this->assertInstanceOf( '\DataUpdate', $update );
	}

	public function itemProvider() {
		return array_map(
			function( ItemContent $itemContent ) { return array( $itemContent ); },
			\Wikibase\Test\TestItemContents::getItems()
		);
	}

	/**
	 * @dataProvider itemProvider
	 * @param ItemContent $itemContent
	 */
	public function testDoUpdate( ItemContent $itemContent ) {
		\TestSites::insertIntoDb();
		$linkLookup = \Wikibase\StoreFactory::getStore()->newSiteLinkCache();

		$itemContent->save( '', null, EDIT_NEW );

		$update = new ItemModificationUpdate( $itemContent );
		$update->doUpdate();

		$item = $itemContent->getItem();

		$expected = count( $item->getSimpleSiteLinks() );
		$actual = $linkLookup->countLinks( array( $item->getId()->getNumericId() ) );

		$this->assertEquals(
			$expected,
			$actual
		);

		// TODO: verify terms

		$update = new \Wikibase\ItemDeletionUpdate( $itemContent );
		$update->doUpdate();
	}

}
