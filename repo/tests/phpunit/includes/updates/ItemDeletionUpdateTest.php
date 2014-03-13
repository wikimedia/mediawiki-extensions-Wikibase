<?php

namespace Wikibase\Test;

use Wikibase\ItemContent;
use Wikibase\ItemDeletionUpdate;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StoreFactory;

/**
 * @covers Wikibase\ItemDeletionUpdate
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group DataUpdate
 * @group Database
 *
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
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$revision = $store->saveEntity( $itemContent->getEntity(), "testing", $GLOBALS['wgUser'], EDIT_NEW );
		$id = $revision->getEntity()->getId()->getNumericId();

		$update = new ItemDeletionUpdate( $itemContent );
		$update->doUpdate();

		$linkLookup = StoreFactory::getStore()->newSiteLinkCache();
		$this->assertEquals( 0, $linkLookup->countLinks( array( $id ) ) );
	}

}
