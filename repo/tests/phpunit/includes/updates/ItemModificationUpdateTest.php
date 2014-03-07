<?php

namespace Wikibase\Test;

use TestSites;
use Wikibase\ItemContent;
use Wikibase\ItemDeletionUpdate;
use Wikibase\ItemModificationUpdate;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StoreFactory;

/**
 * @covers Wikibase\ItemModificationUpdate
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group DataUpdate
 * @group ItemModificationUpdateTest
 * @group Database
 *
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
		TestSites::insertIntoDb();
		$linkLookup = StoreFactory::getStore()->newSiteLinkCache();

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$revision = $store->saveEntity( $itemContent->getEntity(), "testing", $GLOBALS['wgUser'], EDIT_NEW );
		$id = $revision->getEntity()->getId()->getNumericId();

		$update = new ItemModificationUpdate( $itemContent );
		$update->doUpdate();

		$item = $itemContent->getItem();

		$expected = count( $item->getSiteLinks() );
		$actual = $linkLookup->countLinks( array( $id ) );

		$this->assertEquals(
			$expected,
			$actual
		);

		// TODO: verify terms

		$update = new ItemDeletionUpdate( $itemContent );
		$update->doUpdate();
	}

}
