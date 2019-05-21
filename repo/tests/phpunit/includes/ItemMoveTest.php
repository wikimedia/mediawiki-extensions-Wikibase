<?php

namespace Wikibase\Repo\Tests;

use MediaWiki\MediaWikiServices;
use MovePage;
use TestSites;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;
use WikitextContent;

/**
 * Tests prevention of moving pages in and out of the data NS.
 *
 * @group Wikibase
 * @group WikibaseItem
 * @group Database
 *
 * @license GPL-2.0-or-later
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
	protected function setUp() {
		parent::setUp();

		//TODO: remove global TestSites DB setup once we can inject sites sanely.
		static $hasSites = false;

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		if ( !$hasSites ) {
			$sitesTable = MediaWikiServices::getInstance()->getSiteStore();
			$sitesTable->clear();
			$sitesTable->saveSites( TestSites::getSites() );
			$hasSites = true;
		}

		$item = new Item();
		$this->entityRevision = $wikibaseRepo->getEntityStore()->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_NEW );

		$id = $this->entityRevision->getEntity()->getId();
		$this->itemTitle = $wikibaseRepo->getEntityTitleLookup()->getTitleForId( $id );

		$title = Title::newFromText( 'wbmovetest', $this->getDefaultWikitextNS() );
		$this->page = new WikiPage( $title );
		$this->page->doEditContent( new WikitextContent( 'foobar' ), 'test' );
	}

	/**
	 * Tests @see WikibaseItem::getIdForSiteLink
	 * XXX That method doesn't exist
	 *
	 * Moving a regular page into data NS onto an existing item
	 */
	public function testMovePreventionRegularToExistingData() {
		$mp = new MovePage( $this->page->getTitle(), $this->itemTitle );
		$this->assertFalse( $mp->move( $this->getTestUser()->getUser() )->isOK() );
	}

	/**
	 * Moving a regular page into data NS to an invalid location
	 * @todo test other types of entities too!
	 */
	public function testMovePreventionRegularToInvalidData() {
		$itemNamespace = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup()
			->getEntityNamespace( 'item' );
		$to = Title::newFromText( $this->page->getTitle()->getText(), $itemNamespace );
		$mp = new MovePage( $this->page->getTitle(), $to );
		$this->assertFalse( $mp->move( $this->getTestUser()->getUser() )->isOK() );
	}

	/**
	 * Moving a regular page into data NS to an empty (but valid) location
	 */
	public function testMovePreventionRegularToValidData() {
		$mp = new MovePage(
			$this->page->getTitle(),
			WikibaseRepo::getDefaultInstance()->getEntityTitleLookup()
				->getTitleForId( new ItemId( 'Q42' ) )
		);
		$this->assertFalse( $mp->move( $this->getTestUser()->getUser() )->isOK() );
	}

	/**
	 * Moving item page out of data NS onto an existing page
	 */
	public function testMovePreventionDataToExistingRegular() {
		$mp = new MovePage( $this->itemTitle, $this->page->getTitle() );
		$this->assertFalse( $mp->move( $this->getTestUser()->getUser() )->isOK() );
	}

	/**
	 * Moving item page out of data NS onto a non-existing page
	 */
	public function testMovePreventionDataToNonExistingRegular() {
		$mp = new MovePage( $this->itemTitle, Title::newFromText( 'wbmovetestitem' ) );
		$this->assertFalse( $mp->move( $this->getTestUser()->getUser() )->isOK() );
	}

	/**
	 * Moving item to an invalid location in the data NS
	 */
	public function testMovePreventionDataToInvalidData() {
		$itemNamespace = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup()
			->getEntityNamespace( 'item' );
		$mp = new MovePage(
			$this->itemTitle,
			Title::newFromText( $this->page->getTitle()->getText(), $itemNamespace )
		);
		$this->assertFalse( $mp->move( $this->getTestUser()->getUser() )->isOK() );
	}

	/**
	 * Moving item to an valid location in the data NS
	 */
	public function testMovePreventionDataToValidData() {
		$mp = new MovePage(
			$this->itemTitle,
			WikibaseRepo::getDefaultInstance()->getEntityTitleLookup()
				->getTitleForId( new ItemId( 'Q42' ) )
		);
		$this->assertFalse( $mp->move( $this->getTestUser()->getUser() )->isOK() );
	}

}
