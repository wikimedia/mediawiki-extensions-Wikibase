<?php

namespace Wikibase\Test;

use TestSites;
use Title;
use Wikibase\ItemContent;
use Wikibase\ItemDeletionUpdate;
use Wikibase\Repo\WikibaseRepo;

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

	public function setUp() {
		parent::setUp();

		$sitesTable = WikibaseRepo::getDefaultInstance()->getSiteStore();
		$sitesTable->clear();
		$sitesTable->saveSites( TestSites::getSites() );
	}

	public function testConstruct() {
		$title = Title::newFromText( 'ItemDeletionUpdateTest/Dummy' );
		$update = new ItemDeletionUpdate( ItemContent::newEmpty(), $title );
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

		$title = Title::newFromText( 'ItemDeletionUpdateTest/Dummy' );
		$update = new ItemDeletionUpdate( $itemContent, $title );
		$update->doUpdate();

		$linkLookup = WikibaseRepo::getDefaultInstance()->getStore()->newSiteLinkCache();
		$this->assertEquals( 0, $linkLookup->countLinks( array( $id ) ) );
	}

}
