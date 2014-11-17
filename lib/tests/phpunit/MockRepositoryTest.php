<?php

namespace Wikibase\Test;

use User;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\EntityRedirect;

/**
 * @covers Wikibase\Test\MockRepository
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseEntityLookup
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MockRepositoryTest extends \MediaWikiTestCase {

	/**
	 * @var MockRepository|null
	 */
	private $mockRepository = null;

	protected function setUp() {
		parent::setUp();

		$this->mockRepository = new MockRepository();
	}

	public function testHasEntity() {
		$q23 = new ItemId( 'q23' );
		$q42 = new ItemId( 'q42' );

		$p23 = new PropertyId( 'p23' );
		$p42 = new PropertyId( 'p42' );

		$item = Item::newEmpty();
		$item->setId( $q23 );
		$this->mockRepository->putEntity( $item );

		$prop = Property::newFromType( 'string' );
		$prop->setId( $p23 );
		$this->mockRepository->putEntity( $prop );

		// test item
		$this->assertTrue( $this->mockRepository->hasEntity( $q23 ) );
		$this->assertFalse( $this->mockRepository->hasEntity( $q42 ) );

		// test prop
		$this->assertTrue( $this->mockRepository->hasEntity( $p23 ) );
		$this->assertFalse( $this->mockRepository->hasEntity( $p42 ) );
	}

	public function testGetEntity() {
		$item = Item::newEmpty();
		$item->setLabel( 'en', 'foo' );

		// set up a data Item
		$this->mockRepository->putEntity( $item, 23 );
		$itemId = $item->getId();

		// set up another version of the data Item
		$item->setLabel( 'de', 'bar' );
		$this->mockRepository->putEntity( $item, 24 );

		// set up a property
		$prop = Property::newFromType( 'string' );
		$prop->setLabel( 'en', 'foo' );
		$prop->setId( $itemId->getNumericId() ); // same numeric id, different prefix

		$propId = $prop->getId();
		$this->mockRepository->putEntity( $prop );

		// test latest item
		$item = $this->mockRepository->getEntity( $itemId );
		$this->assertNotNull( $item, "Entity " . $itemId );
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Item', $item, "Entity " . $itemId );
		$this->assertEquals( 'foo', $item->getLabel( 'en' ) );
		$this->assertEquals( 'bar', $item->getLabel( 'de' ) );

		// test we can't mess with entities in the repo
		$item->setLabel( 'en', 'STRANGE' );
		$item = $this->mockRepository->getEntity( $itemId );
		$this->assertEquals( 'foo', $item->getLabel( 'en' ) );

		// test latest prop
		$prop = $this->mockRepository->getEntity( $propId );
		$this->assertNotNull( $prop, "Entity " . $propId );
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Property', $prop, "Entity " . $propId );
	}

	public function testGetEntityRevision() {
		$item = Item::newEmpty();
		$item->setLabel( 'en', 'foo' );

		// set up a data Item
		$this->mockRepository->putEntity( $item, 23, "20130101000000" );
		$itemId = $item->getId();

		// set up another version of the data Item
		$item->setLabel( 'de', 'bar' );
		$this->mockRepository->putEntity( $item, 24 );

		// set up a property
		$prop = Property::newFromType( 'string' );
		$prop->setLabel( 'en', 'foo' );
		$prop->setId( $itemId->getNumericId() ); // same numeric id, different prefix

		$propId = $prop->getId();
		$this->mockRepository->putEntity( $prop );

		// test latest item
		$itemRev = $this->mockRepository->getEntityRevision( $itemId );
		$this->assertNotNull( $item, "Entity " . $itemId );
		$this->assertInstanceOf( '\Wikibase\EntityRevision', $itemRev, "Entity " . $itemId );
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Item', $itemRev->getEntity(), "Entity " . $itemId );
		$this->assertEquals( 24, $itemRev->getRevisionId() );

		// test item by rev id
		$itemRev = $this->mockRepository->getEntityRevision( $itemId, 23 );
		$this->assertNotNull( $item, "Entity " . $itemId . "@23" );
		$this->assertInstanceOf( '\Wikibase\EntityRevision', $itemRev, "Entity " . $itemId );
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Item', $itemRev->getEntity(), "Entity " . $itemId );
		$this->assertEquals( 23, $itemRev->getRevisionId() );
		$this->assertEquals( "20130101000000", $itemRev->getTimestamp() );

		// test latest prop
		$propRev = $this->mockRepository->getEntityRevision( $propId );
		$this->assertNotNull( $propRev, "Entity " . $propId );
		$this->assertInstanceOf( '\Wikibase\EntityRevision', $propRev, "Entity " . $propId );
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Property', $propRev->getEntity(), "Entity " . $propId );
	}

	public function testGetItemIdForLink() {
		$item = Item::newEmpty();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		// test item lookup
		$this->mockRepository->putEntity( $item );
		$itemId = $item->getId();

		$this->assertEquals( $itemId, $this->mockRepository->getItemIdForLink( 'enwiki', 'Foo' ) );
		$this->assertEquals( null, $this->mockRepository->getItemIdForLink( 'xywiki', 'Foo' ) );

		// test lookup after item modification
		$item->addSiteLink( new SiteLink( 'enwiki', 'Bar' ), 'set' );
		$this->mockRepository->putEntity( $item );

		$this->assertEquals( null, $this->mockRepository->getItemIdForLink( 'enwiki', 'Foo' ) );
		$this->assertEquals( $itemId, $this->mockRepository->getItemIdForLink( 'enwiki', 'Bar' ) );

		// test lookup after item deletion
		$this->mockRepository->removeEntity( $itemId );

		$this->assertEquals( null, $this->mockRepository->getItemIdForLink( 'enwiki', 'Foo' ) );
		$this->assertEquals( null, $this->mockRepository->getItemIdForLink( 'enwiki', 'Bar' ) );
	}

	public static function provideGetConflictsForItem() {
		$cases = array();

		// #0: same link ---------
		$a = Item::newEmpty();
		$a->setId( 1 );
		$a->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );
		$a->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Foo' );

		$b = Item::newEmpty();
		$b->setId( 2 );
		$b->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );
		$b->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Bar' );

		$cases[] = array( $a, $b, array( array( 'enwiki', 'Foo', 1 ) ) );

		// #1: same site ---------
		$a = Item::newEmpty();
		$a->setId( 1 );
		$a->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$b = Item::newEmpty();
		$b->setId( 2 );
		$b->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Bar' );

		$cases[] = array( $a, $b, array() );

		// #2: same page ---------
		$a = Item::newEmpty();
		$a->setId( 1 );
		$a->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$b = Item::newEmpty();
		$b->setId( 2 );
		$b->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Foo' );

		$cases[] = array( $a, $b, array() );

		// #3: same item ---------
		$a = Item::newEmpty();
		$a->setId( 1 );
		$a->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$cases[] = array( $a, $a, array() );

		return $cases;
	}

	/**
	 * @dataProvider provideGetConflictsForItem
	 */
	public function testGetConflictsForItem( Item $a, Item $b, $expectedConflicts ) {
		$this->mockRepository->putEntity( $a );
		$conflicts = $this->mockRepository->getConflictsForItem( $b );

		$this->assertArrayEquals( $expectedConflicts, $conflicts );
	}

	public static function provideGetLinks() {
		$cases = array();

		$a = Item::newEmpty();
		$a->setId( 1 );
		$a->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );
		$a->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Bar' );

		$b = Item::newEmpty();
		$b->setId( 2 );
		$b->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Bar' );
		$b->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Xoo' );

		$items = array( $a, $b );

		// #0: all ---------
		$cases[] = array( $items,
			array(), // items
			array(), // sites
			array(), // pages
			array(  // expected
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
			$this->mockRepository->putEntity( $item );
		}

		$links = $this->mockRepository->getLinks( $itemIds, $sites, $pages );

		$this->assertArrayEquals( $expectedLinks, $links );
	}

	/**
	 * @dataProvider provideGetLinks
	 */
	public function testCountLinks( array $items, array $itemIds, array $sites, array $pages, array $expectedLinks ) {
		foreach ( $items as $item ) {
			$this->mockRepository->putEntity( $item );
		}

		$n = $this->mockRepository->countLinks( $itemIds, $sites, $pages );

		$this->assertEquals( count( $expectedLinks ), $n );
	}

	public static function provideGetEntities() {
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
		$one = Item::newEmpty();
		$one->setId( 1 );
		$one->setLabel( 'en', 'one' );

		$two = Item::newEmpty();
		$two->setId( 2 );
		$two->setLabel( 'en', 'two' );

		$three = Item::newEmpty();
		$three->setId( 3 );
		$three->setLabel( 'en', 'three' );
		$three->setLabel( 'de', 'drei' );
		$three->setDescription( 'en', 'the third' );

		$prop = Property::newFromType( 'string' );
		$prop->setId( 4 );
		$prop->setLabel( 'en', 'property!' );

		$this->mockRepository->putEntity( $one, 1001 );
		$this->mockRepository->putEntity( $two, 1002 );
		$this->mockRepository->putEntity( $three, 1003 );
		$this->mockRepository->putEntity( $prop, 1101 );

		$one->setLabel( 'de', "eins" );
		$this->mockRepository->putEntity( $one, 1011 );
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
			$entities = $this->mockRepository->getEntities( $ids );

			if ( $expectedError !== false  ) {
				$this->fail( "expected error: " . $expectedError );
			}
		} catch ( \MWException $ex ) {
			if ( $expectedError !== false ) {
				$this->assertInstanceOf( $expectedError, $ex );
			} else {
				$this->fail( "error: " . $ex->getMessage() );
			}
		}

		if ( !is_array( $expected ) ) {
			// expected some kind of special return value, e.g. false.
			$this->assertEquals( $expected, $entities, "return value" );
			return;
		} else {
			$this->assertType( 'array', $entities, "return value" );
		}

		// extract map of entity IDs to label arrays.
		/* @var Entity $e  */
		$actual = array();
		foreach ( $entities as $key => $e ) {
			if ( is_object( $e ) ) {
				$actual[ $e->getId()->getSerialization() ] = $e->getLabels();
			} else {
				$actual[ $key ] = $e;
			}
		}

		// check that we found the right number of entities
		$this->assertEquals( count( $expected ), count( $actual ), "number of entities found" );

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
		$one = Item::newEmpty();
		$one->setId( 1 );

		$one->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Xoo' );
		$one->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Foo' );

		$this->mockRepository->putEntity( $one );

		// check link retrieval
		$this->assertEquals(
			array(
				new SiteLink( 'dewiki', 'Xoo' ),
				new SiteLink( 'enwiki', 'Foo' ),
			),
			$this->mockRepository->getSiteLinksForItem( $one->getId() )
		);

		// check links of unknown id
		$this->assertEmpty( $this->mockRepository->getSiteLinksForItem( new ItemId( 'q123' ) ) );
	}

	public function provideSaveEntity() {
		$item = Item::newEmpty();
		$item->setLabel( 'en', 'one' );

		$secondItem = Item::newEmpty();
		$secondItem->setId( 1 );
		$secondItem->setLabel( 'en', 'one' );
		$secondItem->setLabel( 'it', 'uno' );

		$thirdItem = Item::newEmpty();
		$thirdItem->setId( 1 );
		$thirdItem->setLabel( 'en', 'one' );

		$fourthItem = Item::newEmpty();
		$fourthItem->setId( 123 );
		$fourthItem->setLabel( 'en', 'one two three' );
		$fourthItem->setLabel( 'de', 'eins zwei drei' );

		$fifthItem = Item::newEmpty();
		$fifthItem->setId( 1 );
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
				'error' => 'Wikibase\Lib\Store\StorageException'
			),

			'not exists' => array(
				'entity' => $fourthItem,
				'flags' => EDIT_UPDATE,
				'baseRevid' => false,
				'error' => 'Wikibase\Lib\Store\\StorageException'
			),

			'bad base' => array(
				'entity' => $fifthItem,
				'flags' => EDIT_UPDATE,
				'baseRevid' => 1234,
				'error' => 'Wikibase\Lib\Store\\StorageException'
			),
		);
	}

	/**
	 * @dataProvider provideSaveEntity
	 */
	public function testSaveEntity( Entity $entity, $flags, $baseRevId, $error = null ) {
		$this->setupGetEntities();

		if ( $error !== null ) {
			$this->setExpectedException( $error );
		}

		$rev = $this->mockRepository->saveEntity( $entity, 'f00', $GLOBALS['wgUser'], $flags, $baseRevId );

		$logEntry = $this->mockRepository->getLogEntry( $rev->getRevisionId() );
		$this->assertNotNull( $logEntry );
		$this->assertEquals( $rev->getRevisionId(), $logEntry['revision'] );
		$this->assertEquals( $entity->getId()->getSerialization(), $logEntry['entity'] );
		$this->assertEquals( 'f00', $logEntry['summary'] );

		$this->assertEquals( $entity->getLabels(), $rev->getEntity()->getLabels() );
		$this->assertEquals( $entity->getLabels(), $this->mockRepository->getEntity( $entity->getId() )->getLabels() );

		// test we can't mess with entities in the repo
		$entity->getFingerprint()->setLabel( 'en', 'STRANGE' );
		$entity = $this->mockRepository->getEntity( $entity->getId() );
		$this->assertNotNull( $entity );
		$this->assertNotEquals( 'STRANGE', $entity->getLabel( 'en' ) );
	}

	public function testSaveRedirect() {
		$this->setupGetEntities();

		$q10 = new ItemId( 'Q10' );
		$q1 = new ItemId( 'Q1' );

		$redirect = new EntityRedirect( $q10, $q1 );
		$revId = $this->mockRepository->saveRedirect( $redirect, 'redirected Q10 to Q1', $GLOBALS['wgUser'] );

		$this->assertGreaterThan( 0, $revId );

		$logEntry = $this->mockRepository->getLogEntry( $revId );
		$this->assertNotNull( $logEntry );
		$this->assertEquals( $revId, $logEntry['revision'] );
		$this->assertEquals( $redirect->getEntityId()->getSerialization(), $logEntry['entity'] );
		$this->assertEquals( 'redirected Q10 to Q1', $logEntry['summary'] );

		$this->setExpectedException( 'Wikibase\Lib\Store\UnresolvedRedirectException' );
		$this->mockRepository->getEntity( $q10 );
	}

	public function testGetLogEntry() {
		$this->setupGetEntities();

		$q10 = new ItemId( 'Q10' );
		$q11 = new ItemId( 'Q11' );

		$redirect = new EntityRedirect( $q10, $q11 );
		$revId = $this->mockRepository->saveRedirect( $redirect, 'foo', $GLOBALS['wgUser'], EDIT_NEW );

		$logEntry = $this->mockRepository->getLogEntry( $revId );

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
		$revId = $this->mockRepository->saveRedirect( $redirect, 'foo', $GLOBALS['wgUser'], EDIT_NEW );

		$logEntry = $this->mockRepository->getLatestLogEntryFor( $q10 );

		$this->assertNotNull( $logEntry );
		$this->assertEquals( $revId, $logEntry['revision'] );
		$this->assertEquals( 'Q10', $logEntry['entity'] );
		$this->assertEquals( 'foo', $logEntry['summary'] );

		// second entry
		$redirect = new EntityRedirect( $q10, $q12 );
		$revId = $this->mockRepository->saveRedirect( $redirect, 'bar', $GLOBALS['wgUser'], EDIT_NEW );

		$logEntry = $this->mockRepository->getLatestLogEntryFor( $q10 );

		$this->assertNotNull( $logEntry );
		$this->assertEquals( $revId, $logEntry['revision'] );
		$this->assertEquals( 'Q10', $logEntry['entity'] );
		$this->assertEquals( 'bar', $logEntry['summary'] );
	}

	public function testDeleteEntity( ) {
		$q23 = new ItemId( 'q23' );
		$item = Item::newEmpty();
		$item->setId( $q23 );
		$this->mockRepository->putEntity( $item );

		$this->mockRepository->deleteEntity( $item->getId(), 'testing', $GLOBALS['wgUser'] );
		$this->assertFalse( $this->mockRepository->hasEntity( $item->getId() ) );
	}

	public function testDeleteRedirect( ) {
		$redirect = new EntityRedirect( new ItemId( 'Q11' ), new ItemId( 'Q1' ) );
		$this->mockRepository->putRedirect( $redirect );

		$this->mockRepository->deleteEntity( $redirect->getEntityId(), 'testing', $GLOBALS['wgUser'] );
		$this->assertNull( $this->mockRepository->getEntity( $redirect->getEntityId() ) );
	}

	public function testUpdateWatchlist() {
		$user = User::newFromName( "WikiPageEntityStoreTestUser2" );

		$item = Item::newEmpty();
		$this->mockRepository->saveEntity( $item, 'testing', $user, EDIT_NEW );
		$itemId = $item->getId();

		$this->mockRepository->updateWatchlist( $user, $itemId, true );
		$this->assertTrue(  $this->mockRepository->isWatching( $user, $itemId ) );

		$this->mockRepository->updateWatchlist( $user, $itemId, false );
		$this->assertFalse(  $this->mockRepository->isWatching( $user, $itemId ) );
	}

	public function testUserWasLastToEdit() {
		$user1 = User::newFromName( "WikiPageEntityStoreTestUserWasLastToEdit1" );
		$user2 = User::newFromName( "WikiPageEntityStoreTestUserWasLastToEdit2" );

		// initial revision
		$item = Item::newEmpty();
		$item->setLabel( 'en', 'one' );
		$rev1 = $this->mockRepository->saveEntity( $item, 'testing 1', $user1, EDIT_NEW );
		$itemId = $item->getId();

		$this->assertTrue( $this->mockRepository->userWasLastToEdit( $user1, $itemId, $rev1->getRevisionId() ), 'user was first and last to edit' );
		$this->assertFalse( $this->mockRepository->userWasLastToEdit( $user2, $itemId, $rev1->getRevisionId() ), 'user has not edited yet' );

		// second edit by another user
		$item = $item->copy();
		$item->setLabel( 'en', 'two' );
		$rev2 = $this->mockRepository->saveEntity( $item, 'testing 2', $user2, EDIT_UPDATE );

		$this->assertFalse( $this->mockRepository->userWasLastToEdit( $user1, $itemId, $rev1->getRevisionId() ), 'original user was no longer last to edit' );
		$this->assertTrue( $this->mockRepository->userWasLastToEdit( $user2, $itemId, $rev2->getRevisionId() ), 'second user has just edited' );

		// subsequent edit by the original user
		$item = $item->copy();
		$item->setLabel( 'en', 'three' );
		$rev3 = $this->mockRepository->saveEntity( $item, 'testing 3', $user1, EDIT_UPDATE );

		$this->assertFalse( $this->mockRepository->userWasLastToEdit( $user1, $itemId, $rev1->getRevisionId() ), 'another user had edited at some point' );
		$this->assertTrue( $this->mockRepository->userWasLastToEdit( $user1, $itemId, $rev3->getRevisionId() ), 'original user was last to edit' );
		$this->assertFalse( $this->mockRepository->userWasLastToEdit( $user2, $itemId, $rev2->getRevisionId() ), 'other user was no longer last to edit' );
	}

}
