<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use ContentHandler;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookupException;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\Sql\WikiPageEntityRedirectLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;
use WikiPage;

/**
 * @covers \Wikibase\Repo\Store\Sql\WikiPageEntityRedirectLookup
 *
 * @group Medium
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class WikiPageEntityRedirectLookupTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var ItemId|null
	 */
	private $itemId = null;

	/**
	 * @var ItemId[]
	 */
	private $redirectItemIds = [];

	protected function setUp(): void {
		parent::setUp();

		if ( $this->itemId === null ) {
			$this->setUpEntities();
			$this->setUpNonEntityRedirect();
		}
	}

	private function setUpEntities() {
		$user = $this->getTestUser()->getUser();
		$entityStore = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$item = new Item();
		$entityStore->saveEntity( $item, "WikiPageEntityRedirectLookupTest", $user, EDIT_NEW );

		$redirectItem1 = new Item();
		$entityStore->assignFreshId( $redirectItem1 );
		$redirect = new EntityRedirect( $redirectItem1->getId(), $item->getId() );
		$entityStore->saveRedirect( $redirect, "WikiPageEntityRedirectLookupTest", $user, EDIT_NEW );

		$redirectItem2 = new Item();
		$entityStore->assignFreshId( $redirectItem2 );
		$redirect = new EntityRedirect( $redirectItem2->getId(), $item->getId() );
		$entityStore->saveRedirect( $redirect, "WikiPageEntityRedirectLookupTest", $user, EDIT_NEW );

		$this->itemId = $item->getId();
		$this->redirectItemIds = [ $redirectItem1->getId(), $redirectItem2->getId() ];
	}

	/**
	 * Create a redirect from a non-Entity NS to the entity created, to make sure this doesn't
	 * interfere with WikiPageEntityRedirectLookup (especially getRedirectIds).
	 */
	private function setUpNonEntityRedirect() {
		$entityTitleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$title = $entityTitleLookup->getTitleForId( $this->itemId );

		$wikiText = '#REDIRECT [[' . $title->getFullText() . ']]';

		$page = WikiPage::factory( Title::newFromText( 'Help:WikiPageEntityMetaDataLookupTest' ) );
		$page->doEditContent(
			ContentHandler::makeContent( $wikiText, $page->getTitle() ),
			'test',
			0,
			false,
			$this->getTestUser()->getUser()
		);
	}

	public function testGetRedirectForEntityId() {
		$res = $this->getWikiPageEntityRedirectLookup()->getRedirectForEntityId( $this->redirectItemIds[0] );

		$this->assertEquals( $this->itemId, $res );
	}

	public function testGetRedirectForEntityId_entityDoesNotExist() {
		$this->expectException( EntityRedirectLookupException::class );
		$this->getWikiPageEntityRedirectLookup()->getRedirectForEntityId( new ItemId( 'Q48758903' ) );
	}

	public function testGetRedirectForEntityId_entityNotARedirect() {
		$res = $this->getWikiPageEntityRedirectLookup()->getRedirectForEntityId( $this->itemId );

		$this->assertNull( $res );
	}

	public function testGetRedirectForEntityId_itemsInMainNamespace() {
		$row = [
			'page_id' => 10,
			'rd_namespace' => NS_MAIN,
			'rd_title' => 'Q10'
		];

		$entityRedirectLookup = new WikiPageEntityRedirectLookup(
			$this->getMockEntityTitleLookup(),
			$this->getMockEntityIdLookup(),
			$this->getMockLoadBalancer( $row )
		);

		$redirect = $entityRedirectLookup->getRedirectForEntityId( new ItemId( 'Q2' ) );

		$this->assertEquals( new ItemId( 'Q10' ), $redirect );
	}

	/**
	 * @return EntityTitleStoreLookup
	 */
	private function getMockEntityTitleLookup() {
		$entityTitleLookup = $this->createMock( EntityTitleStoreLookup::class );

		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::makeTitle( NS_MAIN, $id->getSerialization() );
			} ) );

		return $entityTitleLookup;
	}

	/**
	 * @return EntityIdLookup
	 */
	private function getMockEntityIdLookup() {
		$entityIdLookup = $this->createMock( EntityIdLookup::class );

		$entityIdLookup->expects( $this->any() )
			->method( 'getEntityIdForTitle' )
			->will( $this->returnCallback( function( Title $title ) {
				return new ItemId( $title->getText() );
			} ) );

		return $entityIdLookup;
	}

	/**
	 * @param array $row
	 *
	 * @return ILoadBalancer
	 */
	private function getMockLoadBalancer( array $row ) {
		$db = $this->getMockDatabase( $row );

		$loadBalancer = $this->createMock( ILoadBalancer::class );

		$loadBalancer->method( 'getConnection' )
			->will( $this->returnValue( $db ) );

		return $loadBalancer;
	}

	/**
	 * @param array $row
	 *
	 * @return IDatabase
	 */
	private function getMockDatabase( array $row ) {
		$db = $this->createMock( IDatabase::class );

		$db->method( 'selectRow' )
			->will( $this->returnValue( (object)$row ) );

		return $db;
	}

	public function testGetRedirectIds() {
		$res = $this->getWikiPageEntityRedirectLookup()->getRedirectIds( $this->itemId );

		$this->assertEquals( $this->redirectItemIds, $res );
	}

	public function testGetRedirectIds_entityDoesNotExist() {
		$res = $this->getWikiPageEntityRedirectLookup()->getRedirectIds( new ItemId( 'Q48758903' ) );

		$this->assertSame( [], $res );
	}

	private function getWikiPageEntityRedirectLookup() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new WikiPageEntityRedirectLookup(
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getEntityIdLookup(),
			MediaWikiServices::getInstance()->getDBLoadBalancer()
		);
	}

}
