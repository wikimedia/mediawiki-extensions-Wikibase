<?php

namespace Wikibase\Lib\Tests;

use PHPUnit\Framework\TestCase;
use User;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookupException;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;

/**
 * @covers \Wikibase\Lib\Tests\MockRepository
 *
 * @group Wikibase
 * @group WikibaseEntityLookup
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MockRepositoryTest extends TestCase {

	/**
	 * @var MockRepository|null
	 */
	private $repo = null;

	protected function setUp(): void {
		parent::setUp();

		$this->repo = new MockRepository();
	}

	public function testHasEntity() {
		$q23 = new ItemId( 'q23' );
		$q42 = new ItemId( 'q42' );

		$p23 = new NumericPropertyId( 'p23' );
		$p42 = new NumericPropertyId( 'p42' );

		$item = new Item( $q23 );
		$this->repo->putEntity( $item );

		$prop = Property::newFromType( 'string' );
		$prop->setId( $p23 );
		$this->repo->putEntity( $prop );

		// test item
		$this->assertTrue( $this->repo->hasEntity( $q23 ) );
		$this->assertFalse( $this->repo->hasEntity( $q42 ) );

		// test prop
		$this->assertTrue( $this->repo->hasEntity( $p23 ) );
		$this->assertFalse( $this->repo->hasEntity( $p42 ) );
	}

	public function testGetEntity() {
		$item = new Item();
		$item->setLabel( 'en', 'foo' );

		// set up a data Item
		$this->repo->putEntity( $item, 23 );
		$itemId = $item->getId();

		// set up another version of the data Item
		$item->setLabel( 'de', 'bar' );
		$this->repo->putEntity( $item, 24 );

		// set up a property
		$prop = Property::newFromType( 'string' );
		$prop->setLabel( 'en', 'foo' );
		$prop->setId( NumericPropertyId::newFromNumber( $itemId->getNumericId() ) ); // same numeric id, different prefix

		$propId = $prop->getId();
		$this->repo->putEntity( $prop );

		// test latest item
		/** @var Item $item */
		$item = $this->repo->getEntity( $itemId );
		$this->assertNotNull( $item, 'Entity ' . $itemId );
		$this->assertInstanceOf( Item::class, $item, 'Entity ' . $itemId );
		$this->assertEquals( 'foo', $item->getLabels()->getByLanguage( 'en' )->getText() );
		$this->assertEquals( 'bar', $item->getLabels()->getByLanguage( 'de' )->getText() );

		// test we can't mess with entities in the repo
		$item->setLabel( 'en', 'STRANGE' );
		$item = $this->repo->getEntity( $itemId );
		$this->assertEquals( 'foo', $item->getLabels()->getByLanguage( 'en' )->getText() );

		// test latest prop
		$prop = $this->repo->getEntity( $propId );
		$this->assertNotNull( $prop, 'Entity ' . $propId );
		$this->assertInstanceOf( Property::class, $prop, 'Entity ' . $propId );
	}

	public function testGetEntityRevision() {
		$item = new Item();
		$item->setLabel( 'en', 'foo' );

		// set up a data Item
		$this->repo->putEntity( $item, 23, '20130101000000' );
		$itemId = $item->getId();

		// set up another version of the data Item
		$item->setLabel( 'de', 'bar' );
		$this->repo->putEntity( $item, 24 );

		// set up a property
		$prop = Property::newFromType( 'string' );
		$prop->setLabel( 'en', 'foo' );
		$prop->setId( NumericPropertyId::newFromNumber( $itemId->getNumericId() ) ); // same numeric id, different prefix

		$propId = $prop->getId();
		$this->repo->putEntity( $prop );

		// test latest item
		$itemRev = $this->repo->getEntityRevision( $itemId );
		$this->assertNotNull( $item, 'Entity ' . $itemId );
		$this->assertInstanceOf( EntityRevision::class, $itemRev, 'Entity ' . $itemId );
		$this->assertInstanceOf( Item::class, $itemRev->getEntity(), 'Entity ' . $itemId );
		$this->assertEquals( 24, $itemRev->getRevisionId() );

		// test item by rev id
		$itemRev = $this->repo->getEntityRevision( $itemId, 23 );
		$this->assertNotNull( $item, 'Entity ' . $itemId . '@23' );
		$this->assertInstanceOf( EntityRevision::class, $itemRev, 'Entity ' . $itemId );
		$this->assertInstanceOf( Item::class, $itemRev->getEntity(), 'Entity ' . $itemId );
		$this->assertEquals( 23, $itemRev->getRevisionId() );
		$this->assertSame( '20130101000000', $itemRev->getTimestamp() );

		// test latest prop
		$propRev = $this->repo->getEntityRevision( $propId );
		$this->assertNotNull( $propRev, 'Entity ' . $propId );
		$this->assertInstanceOf( EntityRevision::class, $propRev, 'Entity ' . $propId );
		$this->assertInstanceOf( Property::class, $propRev->getEntity(), 'Entity ' . $propId );
	}

	public function testGetItemIdForLink() {
		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		// test item lookup
		$this->repo->putEntity( $item );
		$itemId = $item->getId();

		$this->assertEquals( $itemId, $this->repo->getItemIdForLink( 'enwiki', 'Foo' ) );
		$this->assertNull( $this->repo->getItemIdForLink( 'xywiki', 'Foo' ) );

		// test lookup after item modification
		$item->getSiteLinkList()->setNewSiteLink( 'enwiki', 'Bar' );
		$this->repo->putEntity( $item );

		$this->assertNull( $this->repo->getItemIdForLink( 'enwiki', 'Foo' ) );
		$this->assertEquals( $itemId, $this->repo->getItemIdForLink( 'enwiki', 'Bar' ) );

		// test lookup after item deletion
		$this->repo->removeEntity( $itemId );

		$this->assertNull( $this->repo->getItemIdForLink( 'enwiki', 'Foo' ) );
		$this->assertNull( $this->repo->getItemIdForLink( 'enwiki', 'Bar' ) );
	}

	public function provideGetLinks() {
		$a = new Item( new ItemId( 'Q1' ) );
		$a->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );
		$a->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Bar' );

		$b = new Item( new ItemId( 'Q2' ) );
		$b->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Bar' );
		$b->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Xoo' );

		$items = [ $a, $b ];

		return [
			'all' => [
				$items,
				'numericIds' => null,
				'siteIds' => null,
				'pageNames' => null,
				'expectedLinks' => [
					[ 'enwiki', 'Foo', 1 ],
					[ 'dewiki', 'Bar', 1 ],
					[ 'enwiki', 'Bar', 2 ],
					[ 'dewiki', 'Xoo', 2 ],
				],
			],
			'mismatch' => [
				$items,
				'numericIds' => null,
				'siteIds' => [ 'enwiki' ],
				'pageNames' => [ 'Xoo' ],
				'expectedLinks' => [],
			],
			'by item' => [
				$items,
				'numericIds' => [ 1 ],
				'siteIds' => null,
				'pageNames' => null,
				'expectedLinks' => [
					[ 'enwiki', 'Foo', 1 ],
					[ 'dewiki', 'Bar', 1 ],
				],
			],
			'by site' => [
				$items,
				'numericIds' => null,
				'siteIds' => [ 'enwiki' ],
				'pageNames' => null,
				'expectedLinks' => [
					[ 'enwiki', 'Foo', 1 ],
					[ 'enwiki', 'Bar', 2 ],
				],
			],
			'by page' => [
				$items,
				'numericIds' => null,
				'siteIds' => null,
				'pageNames' => [ 'Bar' ],
				'expectedLinks' => [
					[ 'dewiki', 'Bar', 1 ],
					[ 'enwiki', 'Bar', 2 ],
				],
			],
			'by site and page' => [
				$items,
				'numericIds' => null,
				'siteIds' => [ 'dewiki' ],
				'pageNames' => [ 'Bar' ],
				'expectedLinks' => [
					[ 'dewiki', 'Bar', 1 ],
				],
			],
		];
	}

	/**
	 * @dataProvider provideGetLinks
	 */
	public function testGetLinks(
		array $items,
		?array $numericIds,
		?array $siteIds,
		?array $pageNames,
		array $expectedLinks
	) {
		foreach ( $items as $item ) {
			$this->repo->putEntity( $item );
		}

		$links = $this->repo->getLinks( $numericIds, $siteIds, $pageNames );

		$this->assertEquals( $expectedLinks, $links );
	}

	public function provideGetEntities() {
		return [
			'empty' => [
				'itemIds' => [],
				'expectedLabels' => [],
			],
			'some entities' => [
				'itemIds' => [ 'Q1', 'Q2' ],
				'expectedLabels' => [
					'Q1' => [
						'de' => 'eins',
						'en' => 'one',
					],
					'Q2' => [
						'en' => 'two',
					],
				],
			],
			'bad ID' => [
				'itemIds' => [ 'Q1', 'Q22' ],
				'expectedLabels' => [
					'Q1' => [
						'en' => 'one',
						'de' => 'eins',
					],
					'Q22' => null,
				],
			],
		];
	}

	protected function setupGetEntities() {
		$one = new Item( new ItemId( 'Q1' ) );
		$one->setLabel( 'en', 'one' );

		$two = new Item( new ItemId( 'Q2' ) );
		$two->setLabel( 'en', 'two' );

		$three = new Item( new ItemId( 'Q3' ) );
		$three->setLabel( 'en', 'three' );
		$three->setLabel( 'de', 'drei' );
		$three->setDescription( 'en', 'the third' );

		$prop = new Property( new NumericPropertyId( 'P4' ), null, 'string' );
		$prop->setLabel( 'en', 'property!' );

		$this->repo->putEntity( $one, 1001 );
		$this->repo->putEntity( $two, 1002 );
		$this->repo->putEntity( $three, 1003 );
		$this->repo->putEntity( $prop, 1101 );

		$one->setLabel( 'de', 'eins' );
		$this->repo->putEntity( $one, 1011 );
	}

	/**
	 * @dataProvider provideGetEntities
	 */
	public function testGetEntities( array $itemIds, array $expectedLabels ) {
		$this->setupGetEntities();

		// convert string IDs to EntityId objects
		foreach ( $itemIds as $i => $id ) {
			if ( is_string( $id ) ) {
				$itemIds[$i] = new ItemId( $id );
			}
		}

		/** @var Item[]|null[] $items */
		$items = $this->repo->getEntities( $itemIds );

		$this->assertIsArray( $items, 'return value' );

		// extract map of entity IDs to label arrays.
		$actualLabels = [];
		foreach ( $items as $key => $item ) {
			if ( $item === null ) {
				$actualLabels[$key] = null;
			} else {
				$actualLabels[$key] = $item->getLabels()->toTextArray();
			}
		}

		// check that we found the right number of entities
		$this->assertSame( count( $expectedLabels ), count( $actualLabels ), 'number of entities found' );

		foreach ( $expectedLabels as $id => $labels ) {
			// check that thew correct entity was found
			$this->assertArrayHasKey( $id, $actualLabels );

			if ( $labels === null ) {
				// check that the entity/revision wasn't found
				$this->assertNull( $actualLabels[$id] );
			} else {
				// check that the entity contains the expected labels
				$this->assertEquals( $labels, $actualLabels[ $id ] );
			}
		}
	}

	public function testGetSiteLinksForItem() {
		$one = new Item( new ItemId( 'Q1' ) );

		$one->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Xoo' );
		$one->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$this->repo->putEntity( $one );

		// check link retrieval
		$this->assertEquals(
			[
				new SiteLink( 'dewiki', 'Xoo' ),
				new SiteLink( 'enwiki', 'Foo' ),
			],
			$this->repo->getSiteLinksForItem( $one->getId() )
		);

		// check links of unknown id
		$this->assertSame( [], $this->repo->getSiteLinksForItem( new ItemId( 'q123' ) ) );
	}

	public function provideSaveEntity() {
		$item = new Item();
		$item->setLabel( 'en', 'one' );

		$secondItem = new Item( new ItemId( 'Q1' ) );
		$secondItem->setLabel( 'en', 'one' );
		$secondItem->setLabel( 'it', 'uno' );

		$thirdItem = new Item( new ItemId( 'Q1' ) );
		$thirdItem->setLabel( 'en', 'one' );

		$fourthItem = new Item( new ItemId( 'Q123' ) );
		$fourthItem->setLabel( 'en', 'one two three' );
		$fourthItem->setLabel( 'de', 'eins zwei drei' );

		$fifthItem = new Item( new ItemId( 'Q1' ) );
		$fifthItem->setLabel( 'en', 'one' );
		$fifthItem->setLabel( 'de', 'eins' );

		return [
			'fresh' => [
				'entity' => $item,
				'flags' => EDIT_NEW,
				'baseRevid' => false,
			],

			'update' => [
				'entity' => $secondItem,
				'flags' => EDIT_UPDATE,
				'baseRevid' => 1011,
			],

			'not fresh' => [
				'entity' => $thirdItem,
				'flags' => EDIT_NEW,
				'baseRevid' => false,
				'error' => StorageException::class,
			],

			'not exists' => [
				'entity' => $fourthItem,
				'flags' => EDIT_UPDATE,
				'baseRevid' => false,
				'error' => StorageException::class,
			],

			'bad base' => [
				'entity' => $fifthItem,
				'flags' => EDIT_UPDATE,
				'baseRevid' => 1234,
				'error' => StorageException::class,
			],
		];
	}

	/**
	 * @dataProvider provideSaveEntity
	 */
	public function testSaveEntity( Item $item, $flags, $baseRevId, $error = null ) {
		$this->setupGetEntities();

		if ( $error !== null ) {
			$this->expectException( $error );
		}

		$rev = $this->repo->saveEntity( $item, 'f00', $this->getUserMock(), $flags, $baseRevId );
		$itemId = $item->getId();
		$revisionId = $rev->getRevisionId();

		$logEntry = $this->repo->getLogEntry( $revisionId );
		$this->assertNotNull( $logEntry );
		$this->assertEquals( $revisionId, $logEntry['revision'] );
		$this->assertEquals( $itemId->getSerialization(), $logEntry['entity'] );
		$this->assertEquals( 'f00', $logEntry['summary'] );

		/** @var Item $revisionItem */
		$revisionItem = $rev->getEntity();
		/** @var Item $savedItem */
		$savedItem = $this->repo->getEntity( $itemId );

		$this->assertTrue( $item->getFingerprint()->equals( $revisionItem->getFingerprint() ) );
		$this->assertTrue( $item->getFingerprint()->equals( $savedItem->getFingerprint() ) );

		// test we can't mess with entities in the repo
		$item->setLabel( 'en', 'STRANGE' );
		$savedItem = $this->repo->getEntity( $itemId );
		$this->assertNotNull( $savedItem );
		$this->assertNotEquals( 'STRANGE', $savedItem->getLabels()->getByLanguage( 'en' )->getText() );
	}

	public function testSaveRedirect() {
		$this->setupGetEntities();

		$q10 = new ItemId( 'Q10' );
		$q1 = new ItemId( 'Q1' );

		$redirect = new EntityRedirect( $q10, $q1 );
		$revId = $this->repo->saveRedirect( $redirect, 'redirected Q10 to Q1', $this->getUserMock() );

		$this->assertGreaterThan( 0, $revId );

		$logEntry = $this->repo->getLogEntry( $revId );
		$this->assertNotNull( $logEntry );
		$this->assertEquals( $revId, $logEntry['revision'] );
		$this->assertEquals( $redirect->getEntityId()->getSerialization(), $logEntry['entity'] );
		$this->assertEquals( 'redirected Q10 to Q1', $logEntry['summary'] );

		$this->expectException( RevisionedUnresolvedRedirectException::class );
		$this->repo->getEntity( $q10 );
	}

	public function testGetLogEntry() {
		$this->setupGetEntities();

		$q10 = new ItemId( 'Q10' );
		$q11 = new ItemId( 'Q11' );

		$redirect = new EntityRedirect( $q10, $q11 );
		$revId = $this->repo->saveRedirect( $redirect, 'foo', $this->getUserMock(), EDIT_NEW );

		$logEntry = $this->repo->getLogEntry( $revId );

		$this->assertNotNull( $logEntry );
		$this->assertEquals( $revId, $logEntry['revision'] );
		$this->assertEquals( 'Q10', $logEntry['entity'] );
		$this->assertEquals( 'foo', $logEntry['summary'] );
	}

	public function testGetLatestLogEntryFor() {
		$this->setupGetEntities();

		$q10 = new ItemId( 'Q10' );
		$q11 = new ItemId( 'Q11' );
		$q12 = new ItemId( 'Q12' );

		// first entry
		$redirect = new EntityRedirect( $q10, $q11 );
		$revId = $this->repo->saveRedirect( $redirect, 'foo', $this->getUserMock(), EDIT_NEW );

		$logEntry = $this->repo->getLatestLogEntryFor( $q10 );

		$this->assertNotNull( $logEntry );
		$this->assertEquals( $revId, $logEntry['revision'] );
		$this->assertEquals( 'Q10', $logEntry['entity'] );
		$this->assertEquals( 'foo', $logEntry['summary'] );

		// second entry
		$redirect = new EntityRedirect( $q10, $q12 );
		$revId = $this->repo->saveRedirect( $redirect, 'bar', $this->getUserMock(), EDIT_NEW );

		$logEntry = $this->repo->getLatestLogEntryFor( $q10 );

		$this->assertNotNull( $logEntry );
		$this->assertEquals( $revId, $logEntry['revision'] );
		$this->assertEquals( 'Q10', $logEntry['entity'] );
		$this->assertEquals( 'bar', $logEntry['summary'] );
	}

	public function testDeleteEntity() {
		$item = new Item( new ItemId( 'Q23' ) );
		$this->repo->putEntity( $item );

		$this->repo->deleteEntity( $item->getId(), 'testing', $this->getUserMock() );
		$this->assertFalse( $this->repo->hasEntity( $item->getId() ) );
	}

	public function testPutRedirect() {
		$redirect = new EntityRedirect( new ItemId( 'Q11' ), new ItemId( 'Q1' ) );
		$this->repo->putRedirect( $redirect );

		try {
			$this->repo->getEntityRevision( new ItemId( 'Q11' ) );
			$this->fail( 'getEntityRevision() should fail for redirects' );
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			$this->assertEquals( 'Q1', $ex->getRedirectTargetId()->getSerialization() );
			$this->assertGreaterThan( 0, $ex->getRevisionId() );
			$this->assertNotEmpty( $ex->getRevisionTimestamp() );
		}

		$this->repo->putRedirect( $redirect, 117, '20150505000000' );

		try {
			$this->repo->getEntityRevision( new ItemId( 'Q11' ) );
			$this->fail( 'getEntityRevision() should fail for redirects' );
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			$this->assertEquals( 'Q1', $ex->getRedirectTargetId()->getSerialization() );
			$this->assertEquals( 117, $ex->getRevisionId() );
			$this->assertSame( '20150505000000', $ex->getRevisionTimestamp() );
		}
	}

	public function testDeleteRedirect() {
		$redirect = new EntityRedirect( new ItemId( 'Q11' ), new ItemId( 'Q1' ) );
		$this->repo->putRedirect( $redirect );

		$this->expectException( RevisionedUnresolvedRedirectException::class );
		$this->repo->deleteEntity( $redirect->getEntityId(), 'testing', $this->getUserMock() );
	}

	public function testUpdateWatchlist() {
		$user = User::newFromName( 'WikiPageEntityStoreTestUser2' );

		$item = new Item();
		$this->repo->saveEntity( $item, 'testing', $user, EDIT_NEW );
		$itemId = $item->getId();

		$this->repo->updateWatchlist( $user, $itemId, true );
		$this->assertTrue( $this->repo->isWatching( $user, $itemId ) );

		$this->repo->updateWatchlist( $user, $itemId, false );
		$this->assertFalse( $this->repo->isWatching( $user, $itemId ) );
	}

	public function testUserWasLastToEdit() {
		$user1 = User::newFromName( 'WikiPageEntityStoreTestUserWasLastToEdit1' );
		$user2 = User::newFromName( 'WikiPageEntityStoreTestUserWasLastToEdit2' );

		// initial revision
		$item = new Item( new ItemId( 'Q42' ) );
		$item->setLabel( 'en', 'one' );
		$rev1 = $this->repo->saveEntity( $item, 'testing 1', $user1, EDIT_NEW );
		$itemId = $item->getId();

		$this->assertTrue(
			$this->repo->userWasLastToEdit( $user1, $itemId, $rev1->getRevisionId() ),
			'user was first and last to edit'
		);
		$this->assertFalse(
			$this->repo->userWasLastToEdit( $user2, $itemId, $rev1->getRevisionId() ),
			'user has not edited yet'
		);

		// second edit by another user
		$item = new Item( new ItemId( 'Q42' ) );
		$item->setLabel( 'en', 'two' );
		$rev2 = $this->repo->saveEntity( $item, 'testing 2', $user2, EDIT_UPDATE );

		$this->assertFalse(
			$this->repo->userWasLastToEdit( $user1, $itemId, $rev1->getRevisionId() ),
			'original user was no longer last to edit'
		);
		$this->assertTrue(
			$this->repo->userWasLastToEdit( $user2, $itemId, $rev2->getRevisionId() ),
			'second user has just edited'
		);

		// subsequent edit by the original user
		$item = new Item( new ItemId( 'Q42' ) );
		$item->setLabel( 'en', 'three' );
		$rev3 = $this->repo->saveEntity( $item, 'testing 3', $user1, EDIT_UPDATE );

		$this->assertFalse(
			$this->repo->userWasLastToEdit( $user1, $itemId, $rev1->getRevisionId() ),
			'another user had edited at some point'
		);
		$this->assertTrue(
			$this->repo->userWasLastToEdit( $user1, $itemId, $rev3->getRevisionId() ),
			'original user was last to edit'
		);
		$this->assertFalse(
			$this->repo->userWasLastToEdit( $user2, $itemId, $rev2->getRevisionId() ),
			'other user was no longer last to edit'
		);
	}

	public function testGetRedirectIds() {
		$mock = new MockRepository();

		$q5 = new ItemId( 'Q5' );
		$q55 = new ItemId( 'Q55' );
		$q555 = new ItemId( 'Q555' );

		$mock->putRedirect( new EntityRedirect( $q55, $q5 ) );
		$mock->putRedirect( new EntityRedirect( $q555, $q5 ) );

		$this->assertSame( [], $mock->getRedirectIds( $q55 ), 'no redirects to redirect' );
		$this->assertEquals( [ $q55, $q555 ], $mock->getRedirectIds( $q5 ), 'two redirects' );
	}

	public function testGetRedirectForEntityId() {
		$mock = new MockRepository();

		$q5 = new ItemId( 'Q5' );
		$q55 = new ItemId( 'Q55' );
		$q77 = new ItemId( 'Q77' );

		$mock->putEntity( new Item( $q5 ) );
		$mock->putRedirect( new EntityRedirect( $q55, $q5 ) );

		$this->assertNull( $mock->getRedirectForEntityId( $q5 ), 'not a redirect' );
		$this->assertEquals( $q5, $mock->getRedirectForEntityId( $q55 ) );

		$this->expectException( EntityRedirectLookupException::class );
		$mock->getRedirectForEntityId( $q77 );
	}

	public function testGetLatestRevisionId_Redirect_ReturnsRedirectResponseWithCorrectData() {
		$mock = new MockRepository();
		$entityId = new ItemId( 'Q55' );
		$redirectsTo = new ItemId( 'Q5' );
		$revisionId = 5;
		$mock->putRedirect( new EntityRedirect( $entityId, $redirectsTo ), $revisionId );

		$latestRevisionIdResult = $mock->getLatestRevisionId( $entityId );

		$failTest = function () {
			$this->fail( 'Redirect was expected' );
		};
		list( $gotRevisionId, $gotTargetId ) = $latestRevisionIdResult
			->onNonexistentEntity( $failTest )
			->onConcreteRevision( $failTest )
			->onRedirect(
				function ( $revisionId, $targetId ) {
					return [ $revisionId, $targetId ];
				}
			)
			->map();

		$this->assertEquals( $revisionId, $gotRevisionId );
		$this->assertEquals( $redirectsTo, $gotTargetId );
	}

	private function getUserMock(): User {
		$u = $this->getMockBuilder( User::class )
			->onlyMethods( [ 'getName' ] )
			->getMock();
		$u->method( 'getName' )->willReturn( __CLASS__ );
		return $u;
	}
}
