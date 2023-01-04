<?php

namespace Wikibase\Repo\Tests;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
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
 * @covers \Wikibase\Repo\RepoHooks::onNamespaceIsMovable
 * @covers \Wikibase\Repo\RepoHooks::isNamespaceUsedByLocalEntities
 * @covers \Wikibase\Lib\Store\EntityNamespaceLookup
 */
class ItemMoveTest extends MediaWikiIntegrationTestCase {

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
	protected function setUp(): void {
		parent::setUp();

		//TODO: remove global TestSites DB setup once we can inject sites sanely.
		static $hasSites = false;

		if ( !$hasSites ) {
			$sitesTable = MediaWikiServices::getInstance()->getSiteStore();
			$sitesTable->clear();
			$sitesTable->saveSites( TestSites::getSites() );
			$hasSites = true;
		}

		$item = new Item();
		$this->entityRevision = WikibaseRepo::getEntityStore()->saveEntity( $item, '', $this->getTestUser()->getUser(), EDIT_NEW );

		$id = $this->entityRevision->getEntity()->getId();
		$this->itemTitle = WikibaseRepo::getEntityTitleStoreLookup()->getTitleForId( $id );

		$title = Title::makeTitle( $this->getDefaultWikitextNS(), 'Wbmovetest' );
		$this->page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		$this->page->doUserEditContent(
			new WikitextContent( 'foobar' ),
			$this->getTestUser()->getUser(),
			'test'
		);
	}

	/**
	 * Tests @see WikibaseItem::getIdForSiteLink
	 * XXX That method doesn't exist
	 *
	 * Moving a regular page into data NS onto an existing item
	 */
	public function testMovePreventionRegularToExistingData() {
		$mp = $this->getServiceContainer()
			->getMovePageFactory()
			->newMovePage( $this->page->getTitle(), $this->itemTitle );
		$this->assertFalse( $mp->move( $this->getTestUser()->getUser() )->isOK() );
	}

	/**
	 * Moving a regular page into data NS to an invalid location
	 * @todo test other types of entities too!
	 */
	public function testMovePreventionRegularToInvalidData() {
		$itemNamespace = WikibaseRepo::getEntityNamespaceLookup()
			->getEntityNamespace( 'item' );
		$to = Title::makeTitle( $itemNamespace, $this->page->getTitle()->getText() );
		$mp = $this->getServiceContainer()
			->getMovePageFactory()
			->newMovePage( $this->page->getTitle(), $to );
		$this->assertFalse( $mp->move( $this->getTestUser()->getUser() )->isOK() );
	}

	/**
	 * Moving a regular page into data NS to an empty (but valid) location
	 */
	public function testMovePreventionRegularToValidData() {
		$mp = $this->getServiceContainer()
			->getMovePageFactory()
			->newMovePage(
				$this->page->getTitle(),
				WikibaseRepo::getEntityTitleStoreLookup()
					->getTitleForId( new ItemId( 'Q42' ) )
			);
		$this->assertFalse( $mp->move( $this->getTestUser()->getUser() )->isOK() );
	}

	/**
	 * Moving item page out of data NS onto an existing page
	 */
	public function testMovePreventionDataToExistingRegular() {
		$mp = $this->getServiceContainer()
			->getMovePageFactory()
			->newMovePage( $this->itemTitle, $this->page->getTitle() );
		$this->assertFalse( $mp->move( $this->getTestUser()->getUser() )->isOK() );
	}

	/**
	 * Moving item page out of data NS onto a non-existing page
	 */
	public function testMovePreventionDataToNonExistingRegular() {
		$mp = $this->getServiceContainer()
			->getMovePageFactory()
			->newMovePage( $this->itemTitle, Title::makeTitle( NS_MAIN, 'Wbmovetestitem' ) );
		$this->assertFalse( $mp->move( $this->getTestUser()->getUser() )->isOK() );
	}

	/**
	 * Moving item to an invalid location in the data NS
	 */
	public function testMovePreventionDataToInvalidData() {
		$itemNamespace = WikibaseRepo::getEntityNamespaceLookup()
			->getEntityNamespace( 'item' );
		$mp = $this->getServiceContainer()
			->getMovePageFactory()
			->newMovePage(
				$this->itemTitle,
				Title::makeTitle( $itemNamespace, $this->page->getTitle()->getText() )
			);
		$this->assertFalse( $mp->move( $this->getTestUser()->getUser() )->isOK() );
	}

	/**
	 * Moving item to an valid location in the data NS
	 */
	public function testMovePreventionDataToValidData() {
		$mp = $this->getServiceContainer()
			->getMovePageFactory()
			->newMovePage(
				$this->itemTitle,
				WikibaseRepo::getEntityTitleStoreLookup()
					->getTitleForId( new ItemId( 'Q42' ) )
			);
		$this->assertFalse( $mp->move( $this->getTestUser()->getUser() )->isOK() );
	}

}
