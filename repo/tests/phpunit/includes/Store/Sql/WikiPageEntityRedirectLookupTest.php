<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Store\Sql;

use ContentHandler;
use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookupException;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\Sql\WikiPageEntityRedirectLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\SelectQueryBuilder;

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

	private function setUpEntities(): void {
		$user = $this->getTestUser()->getUser();
		$entityStore = WikibaseRepo::getEntityStore();

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
	private function setUpNonEntityRedirect(): void {
		$entityTitleLookup = WikibaseRepo::getEntityTitleLookup();
		$title = $entityTitleLookup->getTitleForId( $this->itemId );

		$wikiText = '#REDIRECT [[' . $title->getFullText() . ']]';

		$title = Title::makeTitle( NS_HELP, 'WikiPageEntityMetaDataLookupTest' );
		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
		$page->doUserEditContent(
			ContentHandler::makeContent( $wikiText, $page->getTitle() ),
			$this->getTestUser()->getUser(),
			'test'
		);
	}

	public function testGetRedirectForEntityId(): void {
		$res = $this->getWikiPageEntityRedirectLookup()->getRedirectForEntityId( $this->redirectItemIds[0] );

		$this->assertEquals( $this->itemId, $res );
	}

	public function testGetRedirectForEntityId_entityDoesNotExist(): void {
		$this->expectException( EntityRedirectLookupException::class );
		$this->getWikiPageEntityRedirectLookup()->getRedirectForEntityId( new ItemId( 'Q48758903' ) );
	}

	public function testGetRedirectForEntityId_entityNotARedirect(): void {
		$res = $this->getWikiPageEntityRedirectLookup()->getRedirectForEntityId( $this->itemId );

		$this->assertNull( $res );
	}

	public function testGetRedirectForEntityId_itemsInMainNamespace(): void {
		$row = [
			'page_id' => 10,
			'rd_namespace' => NS_MAIN,
			'rd_title' => 'Q10',
		];

		$entityRedirectLookup = new WikiPageEntityRedirectLookup(
			$this->getMockEntityTitleLookup(),
			$this->getMockEntityIdLookup(),
			$this->getMockRepoDomainDb( $row )
		);

		$redirect = $entityRedirectLookup->getRedirectForEntityId( new ItemId( 'Q2' ) );

		$this->assertEquals( new ItemId( 'Q10' ), $redirect );
	}

	private function getMockEntityTitleLookup(): EntityTitleStoreLookup {
		$entityTitleLookup = $this->createMock( EntityTitleStoreLookup::class );

		$entityTitleLookup->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $id ) {
				return Title::makeTitle( NS_MAIN, $id->getSerialization() );
			} );

		return $entityTitleLookup;
	}

	private function getMockEntityIdLookup(): EntityIdLookup {
		$entityIdLookup = $this->createMock( EntityIdLookup::class );

		$entityIdLookup->method( 'getEntityIdForTitle' )
			->willReturnCallback( function( Title $title ) {
				return new ItemId( $title->getText() );
			} );

		return $entityIdLookup;
	}

	private function getMockRepoDomainDb( array $row ): RepoDomainDb {
		$db = $this->getMockDatabase( $row );
		$connections = $this->createMock( ConnectionManager::class );
		$methods = [ 'getReadConnection', 'getWriteConnection' ];
		foreach ( $methods as $method ) {
			$connections->method( $method )
				->willReturn( $db );
		}
		$repoDomainDb = $this->createMock( RepoDomainDb::class );
		$repoDomainDb->method( 'connections' )
			->willReturn( $connections );
		return $repoDomainDb;
	}

	private function getMockDatabase( array $row ): IDatabase {
		$selectQueryBuilder = $this->createMock( SelectQueryBuilder::class );
		$selectQueryBuilder->method( 'fetchRow' )
			->willReturn( (object)$row );
		$selectQueryBuilder->method( $this->anythingBut( 'fetchRow' ) )
			->willReturnSelf();

		$db = $this->createMock( IDatabase::class );
		$db->method( 'newSelectQueryBuilder' )
			->willReturn( $selectQueryBuilder );

		return $db;
	}

	public function testGetRedirectIds(): void {
		$res = $this->getWikiPageEntityRedirectLookup()->getRedirectIds( $this->itemId );

		$this->assertEquals( $this->redirectItemIds, $res );
	}

	public function testGetRedirectIds_entityDoesNotExist(): void {
		$res = $this->getWikiPageEntityRedirectLookup()->getRedirectIds( new ItemId( 'Q48758903' ) );

		$this->assertSame( [], $res );
	}

	private function getWikiPageEntityRedirectLookup(): WikiPageEntityRedirectLookup {
		return new WikiPageEntityRedirectLookup(
			WikibaseRepo::getEntityTitleStoreLookup(),
			WikibaseRepo::getEntityIdLookup(),
			WikibaseRepo::getRepoDomainDbFactory()->newRepoDb()
		);
	}

}
