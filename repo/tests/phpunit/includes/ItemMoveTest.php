<?php

namespace Wikibase\Test;

use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\NamespaceUtils;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;
use WikitextContent;

/**
 * Tests prevention of moving pages in and out of the data NS.
 *
 * @group Wikibase
 * @group WikibaseItem
 * @group WikibaseRepo
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ItemMoveTest extends \MediaWikiTestCase {

	//@todo: make this a baseclass to use with all types of entities.

	/**
	 * @var EntityRevision
	 */
	protected $entityRevision;

	/**
	 * @var Title
	 */
	protected $itemTitle;

	/**
	 * @var WikiPage
	 */
	protected $page;

	/**
	 * This is to set up the environment
	 */
	public function setUp() {
		if ( defined( 'WBC_VERSION' ) ) {
			$this->markTestSkipped( 'Cannot run ItemMoveTest when the client is hooked up to the repo' );
		}

		parent::setUp();

		//TODO: remove global TestSites DB setup once we can inject sites sanely.
		static $hasSites = false;

		if ( !$hasSites ) {
			\TestSites::insertIntoDb();
			$hasSites = true;
		}

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$item = Item::newEmpty();
		$this->entityRevision = $store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_NEW );

		$id = $this->entityRevision->getEntity()->getId();
		$this->itemTitle = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup()->getTitleForId( $id );

		$title = Title::newFromText( 'wbmovetest', $this->getDefaultWikitextNS() );
		$this->page =  new WikiPage( $title );
		$this->page->doEditContent( new WikitextContent( 'foobar' ), 'test' );
	}

	/**
	 * Tests @see WikibaseItem::getIdForSiteLink
	 */
	public function testMovePrevention() {
		$titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();

		// Moving a regular page into data NS onto an existing item
		$title = $this->itemTitle;
		$this->assertInstanceOf( 'Title', $title ); // sanity check

		$this->assertFalse( $this->page->getTitle()->moveTo( $title ) === true );

		// Moving a regular page into data NS to an invalid location
		$title = Title::newFromText( $this->page->getTitle()->getText(),
			NamespaceUtils::getEntityNamespace( CONTENT_MODEL_WIKIBASE_ITEM ) ); //@todo: test other types of entities too!
		$this->assertFalse( $this->page->getTitle()->moveTo( $title ) === true );

		// Moving a regular page into data NS to an empty (but valid) location
		$title = $titleLookup->getTitleForId( new ItemId( 'Q42' ) );
		$this->assertFalse( $this->page->getTitle()->moveTo( $title ) === true );

		// Moving item page out of data NS onto an existing page
		$title = $this->page->getTitle();
		$this->assertFalse( $this->itemTitle->moveTo( $title ) === true );

		// Moving item page out of data NS onto a non-existing page
		$title = Title::newFromText( 'wbmovetestitem' );
		$this->assertFalse( $this->itemTitle->moveTo( $title ) === true );

		// Moving item to an invalid location in the data NS
		$title = Title::newFromText( $this->page->getTitle()->getText(),
			NamespaceUtils::getEntityNamespace( CONTENT_MODEL_WIKIBASE_ITEM ) );
		$this->assertFalse( $this->itemTitle->moveTo( $title ) === true );

		// Moving item to an valid location in the data NS
		$title = $titleLookup->getTitleForId( new ItemId( 'Q42' ) );
		$this->assertFalse( $this->itemTitle->moveTo( $title ) === true );
	}

}

