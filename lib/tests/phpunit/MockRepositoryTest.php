<?php

namespace Wikibase\Lib\Tests;

use MWException;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookupException;
use Wikibase\DataModel\SiteLink;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;

/**
 * @covers Wikibase\Lib\Tests\MockRepository
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseEntityLookup
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MockRepositoryTest extends \MediaWikiTestCase {

	/**
	 * @var MockRepository|null
	 */
	private $repo = null;

	protected function setUp() {
		parent::setUp();

		$this->repo = new MockRepository();
	}

	public function testHasEntity() {
		$q23 = new ItemId( 'q23' );
		$q42 = new ItemId( 'q42' );

		$p23 = new PropertyId( 'p23' );
		$p42 = new PropertyId( 'p42' );

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
		$prop->setId( $itemId->getNumericId() ); // same numeric id, different prefix

		$propId = $prop->getId();
		$this->repo->putEntity( $prop );

		// test latest item
		/** @var Item $item */
		$item = $this->repo->getEntity( $itemId );
		$this->assertNotNull( $item, 'Entity ' . $itemId );
		$this->assertInstanceOf( Item::class, $item, 'Entity ' . $itemId );
		$this->assertEquals( 'foo', $item->getFingerprint()->getLabel( 'en' )->getText() );
		$this->assertEquals( 'bar', $item->getFingerprint()->getLabel( 'de' )->getText() );

		// test we can't mess with entities in the repo
		$item->setLabel( 'en', 'STRANGE' );
		$item = $this->repo->getEntity( $itemId );
		$this->assertEquals( 'foo', $item->getFingerprint()->getLabel( 'en' )->getText() );

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
		$prop->setId( $itemId->getNumericId() ); // same numeric id, different prefix

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
		$this->assertEquals( '20130101000000', $itemRev->getTimestamp() );

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
		$cases = array();

		$a = new Item( new ItemId( 'Q1' ) );
		$a->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );
		$a->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Bar' );

		$b = new Item( new ItemId( 'Q2' ) );
		$b->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Bar' );
		$b->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Xoo' );

		$items = array( $a, $b );

		// #0: all ---------
		$cases[] = array( $items,
			array(), // items
			array(), // sites
			array(), // pages
			array( // expected
				array( 'enwiki', 'Foo', 1 ),
				array( 'dewiki', 'Bar', 1 ),
				array( 'enwiki', 'Bar', 2 ),
				array( 'dewiki', 'Xoo', 2 ),
			)
		);

		// #1: mismatch ---------
		$cases[] = array( $items,
			array(), // items
			array( 'enwiki' ), // sites
			array( 'Xoo' ), // pages
			array() // expected
		);

		// #2: by item ---------
		$cases[] = array( $items,
			array( 1 ), // items
			array(), // sites
			array(), // pages
			array( // expected
				array( 'enwiki', 'Foo', 1 ),
				array( 'dewiki', 'Bar', 1 ),
			)
		);

		// #3: by site ---------
		$cases[] = array( $items,
			array(), // items
			array( 'enwiki' ), // sites
			array(), // pages
			array( // expected
				array( 'enwiki', 'Foo', 1 ),
				array( 'enwiki', 'Bar', 2 ),
			)
		);

		// #4: by page ---------
		$cases[] = array( $items,
			array(), // items
			array(), // sites
			array( 'Bar' ), // pages
			array( // expected
				array( 'dewiki', 'Bar', 1 ),
				array( 'enwiki', 'Bar', 2 ),
			)
		);

		// #5: by site and page ---------
		$cases[] = array( $items,
			array(), // items
			array( 'dewiki' ), // sites
			array( 'Bar' ), // pages
			array( // expected
				array( 'dewiki', 'Bar', 1 ),
			)
		);

		return $cases;
	}

	/**
	 * @dataProvider provideGetLinks
	 */
	public function testGetLinks( array $items, array $itemIds, array $sites, array $pages, array $expectedLinks ) {
		foreach ( $items as $item ) {
			$this->repo->putEntity( $item );
		}

		$links = $this->repo->getLinks( $itemIds, $sites, $pages );

		$this->assertArrayEquals( $expectedLinks, $links );
	}

	public function provideGetEntities() {
		return array(
			array( // #0: empty
				array(), // ids
				array(), // expected
			),

			array( // #1: some entities
				array( // ids
					'Q1',
					'Q2',
				),
				array( // expected
					'Q1' => array(
						'de' => 'eins',
						'en' => 'one',
					),
					'Q2' => array(
						'en' => 'two',
					),
				),
			),

			array( // #2: bad ID
				array( 'Q1', 'Q22' ), // ids
				array( // expected
					'Q1' => array(
						'en' => 'one',
						'de' => 'eins',
					),
					'Q22' => null,
				),
			)
		);
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

		$prop = Property::newFromType( 'string' );
		$prop->setId( 4 );
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
	public function testGetEntities( $ids, $expected, $expectedError = false ) {
		$this->setupGetEntities();

		$idParser = new BasicEntityIdParser();

		// convert string IDs to EntityId objects
		foreach ( $ids as $i => $id ) {
			if ( is_string( $id ) ) {
				$ids[ $i ] = $idParser->parse( $id );
			}
		}

		$entities = false;

		// do it!
		try {
			$entities = $this->repo->getEntities( $ids );

			if ( $expectedError !== false ) {
				$this->fail( 'expected error: ' . $expectedError );
			}
		} catch ( MWException $ex ) {
			if ( $expectedError !== false ) {
				$this->assertInstanceOf( $expectedError, $ex );
			} else {
				$this->fail( 'error: ' . $ex->getMessage() );
			}
		}

		if ( !is_array( $expected ) ) {
			// expected some kind of special return value, e.g. false.
			$this->assertEquals( $expected, $entities, 'return value' );
			return;
		} else {
			$this->assertType( 'array', $entities, 'return value' );
		}

		// extract map of entity IDs to label arrays.
		/* @var EntityDocument $e  */
		$actual = array();
		foreach ( $entities as $key => $e ) {
			if ( is_object( $e ) ) {
				$actual[ $e->getId()->getSerialization() ] = $e->getFingerprint()->getLabels()->toTextArray();
			} else {
				$actual[ $key ] = $e;
			}
		}

		// check that we found the right number of entities
		$this->assertEquals( count( $expected ), count( $actual ), 'number of entities found' );

		foreach ( $expected as $id => $labels ) {
			// check that thew correct entity was found
			$this->assertArrayHasKey( $id, $actual );

			if ( is_array( $labels ) ) {
				// check that the entity contains the expected labels
				$this->assertArrayEquals( $labels, $actual[$id] );
			} else {
				// typically, $labels would be null here.
				// check that the entity/revision wasn't found
				$this->assertEquals( $labels, $actual[$id] );
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
			array(
				new SiteLink( 'dewiki', 'Xoo' ),
				new SiteLink( 'enwiki', 'Foo' ),
			),
			$this->repo->getSiteLinksForItem( $one->getId() )
		);

		// check links of unknown id
		$this->assertEmpty( $this->repo->getSiteLinksForItem( new ItemId( 'q123' ) ) );
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

		return array(
			'fresh' => array(
				'entity' => $item,
				'flags' => EDIT_NEW,
				'baseRevid' => false,
			),

			'update' => array(
				'entity' => $secondItem,
				'flags' => EDIT_UPDATE,
				'baseRevid' => 1011,
			),

			'not fresh' => array(
				'entity' => $thirdItem,
				'flags' => EDIT_NEW,
				'baseRevid' => false,
				'error' => StorageException::class
			),

			'not exists' => array(
				'entity' => $fourthItem,
				'flags' => EDIT_UPDATE,
				'baseRevid' => false,
				'error' => StorageException::class
			),

			'bad base' => array(
				'entity' => $fifthItem,
				'flags' => EDIT_UPDATE,
				'baseRevid' => 1234,
				'error' => StorageException::class
			),
		);
	}

	/**
	 * @dataProvider provideSaveEntity
	 */
	public function testSaveEntity( EntityDocument $entity, $flags, $baseRevId, $error = null ) {
		$this->setupGetEntities();

		if ( $error !== null ) {
			$this->setExpectedException( $error );
		}

		$rev = $this->repo->saveEntity( $entity, 'f00', $GLOBALS['wgUser'], $flags, $baseRevId );

		$logEntry = $this->repo->getLogEntry( $rev->getRevisionId() );
		$this->assertNotNull( $logEntry );
		$this->assertEquals( $rev->getRevisionId(), $logEntry['revision'] );
		$this->assertEquals( $entity->getId()->getSerialization(), $logEntry['entity'] );
		$this->assertEquals( 'f00', $logEntry['summary'] );

		$savedEntity = $this->repo->getEntity( $entity->getId() );

		$this->assertTrue( $entity->getFingerprint()->equals( $rev->getEntity()->getFingerprint() ) );
		$this->assertTrue( $entity->getFingerprint()->equals( $savedEntity->getFingerprint() ) );

		// test we can't mess with entities in the repo
		$entity->getFingerprint()->setLabel( 'en', 'STRANGE' );
		$entity = $this->repo->getEntity( $entity->getId() );
		$this->assertNotNull( $entity );
		$this->assertNotEquals( 'STRANGE', $entity->getFingerprint()->getLabel( 'en' )->getText() );
	}

	public function testSaveRedirect() {
		$this->setupGetEntities();

		$q10 = new ItemId( 'Q10' );
		$q1 = new ItemId( 'Q1' );

		$redirect = new EntityRedirect( $q10, $q1 );
		$revId = $this->repo->saveRedirect( $redirect, 'redirected Q10 to Q1', $GLOBALS['wgUser'] );

		$this->assertGreaterThan( 0, $revId );

		$logEntry = $this->repo->getLogEntry( $revId );
		$this->assertNotNull( $logEntry );
		$this->assertEquals( $revId, $logEntry['revision'] );
		$this->assertEquals( $redirect->getEntityId()->getSerialization(), $logEntry['entity'] );
		$this->assertEquals( 'redirected Q10 to Q1', $logEntry['summary'] );

		$this->setExpectedException( RevisionedUnresolvedRedirectException::class );
		$this->repo->getEntity( $q10 );
	}

	public function testGetLogEntry() {
		$this->setupGetEntities();

		$q10 = new ItemId( 'Q10' );
		$q11 = new ItemId( 'Q11' );

		$redirect = new EntityRedirect( $q10, $q11 );
		$revId = $this->repo->saveRedirect( $redirect, 'foo', $GLOBALS['wgUser'], EDIT_NEW );

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
		$revId = $this->repo->saveRedirect( $redirect, 'foo', $GLOBALS['wgUser'], EDIT_NEW );

		$logEntry = $this->repo->getLatestLogEntryFor( $q10 );

		$this->assertNotNull( $logEntry );
		$this->assertEquals( $revId, $logEntry['revision'] );
		$this->assertEquals( 'Q10', $logEntry['entity'] );
		$this->assertEquals( 'foo', $logEntry['summary'] );

		// second entry
		$redirect = new EntityRedirect( $q10, $q12 );
		$revId = $this->repo->saveRedirect( $redirect, 'bar', $GLOBALS['wgUser'], EDIT_NEW );

		$logEntry = $this->repo->getLatestLogEntryFor( $q10 );

		$this->assertNotNull( $logEntry );
		$this->assertEquals( $revId, $logEntry['revision'] );
		$this->assertEquals( 'Q10', $logEntry['entity'] );
		$this->assertEquals( 'bar', $logEntry['summary'] );
	}

	public function testDeleteEntity() {
		$item = new Item( new ItemId( 'Q23' ) );
		$this->repo->putEntity( $item );

		$this->repo->deleteEntity( $item->getId(), 'testing', $GLOBALS['wgUser'] );
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
			$this->assertEquals( '20150505000000', $ex->getRevisionTimestamp() );
		}
	}

	public function testDeleteRedirect() {
		$redirect = new EntityRedirect( new ItemId( 'Q11' ), new ItemId( 'Q1' ) );
		$this->repo->putRedirect( $redirect );

		$this->setExpectedException( RevisionedUnresolvedRedirectException::class );
		$this->repo->deleteEntity( $redirect->getEntityId(), 'testing', $GLOBALS['wgUser'] );
	}

	public function testUpdateWatchlist() {
		$user = User::newFromName( 'WikiPageEntityStoreTestUser2' );

		$item = new Item( new ItemId( 'Q77534' ) );
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

		$this->assertEmpty( $mock->getRedirectIds( $q55 ), 'no redirects to redirect' );
		$this->assertEquals( array( $q55, $q555 ), $mock->getRedirectIds( $q5 ), 'two redirects' );
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

		$this->setExpectedException( EntityRedirectLookupException::class );
		$mock->getRedirectForEntityId( $q77 );
	}

}
