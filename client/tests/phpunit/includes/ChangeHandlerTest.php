<?php

namespace Wikibase\Test;

use ArrayIterator;
use Diff\Differ\MapDiffer;
use Site;
use SiteList;
use Title;
use Wikibase\Change;
use Wikibase\ChangeHandler;
use Wikibase\ChangesTable;
use Wikibase\Client\Changes\AffectedPagesFinder;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\EntityChange;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\NamespaceChecker;
use Wikibase\PageUpdater;

/**
 * @covers Wikibase\ChangeHandler
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseChange
 * @group ChangeHandlerTest
 *
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ChangeHandlerTest extends \MediaWikiTestCase {

	/** @var Site $site */
	protected $site;

	public function setUp() {
		parent::setUp();

		$this->site = new \MediaWikiSite();
		$this->site->setGlobalId( 'enwiki' );
		$this->site->setLanguageCode( 'en' );
		$this->site->addNavigationId( 'en' );
	}

	private function newChangeHandler( PageUpdater $updater = null, array $entities = array() ) {
		$repo = $this->getMockRepo( $entities );

		$usageLookup = $this->getUsageLookup( $repo );
		$titleFactory = $this->getTitleFactory( $entities );

		$changeFactory = TestChanges::getEntityChangeFactory();

		if ( !$updater ) {
			$updater = new MockPageUpdater();
		}

		$namespaceChecker = new NamespaceChecker( array(), array( NS_MAIN ) );

		// @todo: mock the finder directly
		$affectedPagesFinder = new AffectedPagesFinder(
			$usageLookup,
			$namespaceChecker,
			$titleFactory,
			'enwiki',
			'en',
			false
		);

		$handler = new ChangeHandler(
			$changeFactory,
			$affectedPagesFinder,
			$titleFactory,
			$updater,
			$repo,
			'enwiki',
			true
		);

		return $handler;
	}

	private function getMockRepo( array $entities = array() ) {
		$repo = new MockRepository();

		// entity 1, revision 11
		$entity1 = Item::newEmpty();
		$entity1->setId( new ItemId( 'q1' ) );
		$entity1->setLabel( 'en', 'one' );
		$repo->putEntity( $entity1, 11 );

		// entity 1, revision 12
		$entity1->setLabel( 'de', 'eins' );
		$repo->putEntity( $entity1, 12 );

		// entity 1, revision 13
		$entity1->setLabel( 'it', 'uno' );
		$repo->putEntity( $entity1, 13 );

		// entity 1, revision 1111
		$entity1->setDescription( 'en', 'the first' );
		$repo->putEntity( $entity1, 1111 );

		// entity 2, revision 21
		$entity1 = Item::newEmpty();
		$entity1->setId( new ItemId( 'q2' ) );
		$entity1->setLabel( 'en', 'two' );
		$repo->putEntity( $entity1, 21 );

		// entity 2, revision 22
		$entity1->setLabel( 'de', 'zwei' );
		$repo->putEntity( $entity1, 22 );

		// entity 2, revision 23
		$entity1->setLabel( 'it', 'due' );
		$repo->putEntity( $entity1, 23 );

		// entity 2, revision 1211
		$entity1->setDescription( 'en', 'the second' );
		$repo->putEntity( $entity1, 1211 );

		$this->updateMockRepo( $repo, $entities );

		return $repo;
	}

	/**
	 * @param array $values
	 * @param EntityDiff|null $diff
	 *
	 * @return EntityChange
	 */
	public static function makeChange( array $values, EntityDiff $diff = null ) {
		if ( !isset( $values['info'] ) ) {
			$values['info'] = array();
		}

		if ( !isset( $values['info']['metadata'] ) ) {
			$values['info']['metadata'] = array();
		}

		if ( !isset( $values['info']['metadata']['rev_id'] ) && isset( $values['revision_id'] ) ) {
			$values['info']['metadata']['rev_id'] = $values[ 'revision_id' ];
		}

		if ( !isset( $values['info']['metadata']['user_text'] ) && isset( $values['user_id'] ) ) {
			$values['info']['metadata']['user_text'] = "User" . $values['user_id'];
		}

		if ( !isset( $values['info']['metadata']['parent_id'] ) && isset( $values['parent_id'] ) ) {
			$values['info']['metadata']['parent_id'] = $values['parent_id'];
		}

		if ( !isset( $values['info']['metadata']['parent_id'] ) ) {
			$values['info']['metadata']['parent_id'] = 0;
		}

		$values['info'] = serialize( $values['info'] );

		/* @var EntityChange $change */
		$table = ChangesTable::singleton();
		$change = $table->newRow( $values, true );

		if ( $diff ) {
			$change->setDiff( $diff );
		}

		return $change;
	}

	public static function makeDiff( $type, $before, $after ) {
		$differ = new MapDiffer( true );

		$diffOps = $differ->doDiff( $before, $after );
		$diff = EntityDiff::newForType( $type, $diffOps );

		return $diff;
	}

	public static function provideGroupChangesByEntity() {
		$entity1 = 'Q1';
		$entity2 = 'Q2';

		$changes = array( // $changes

			self::makeChange( array(
				'id' => 1,
				'type' => 'wikibase-item~update',
				'time' => '20130101010101',
				'object_id' => $entity1,
				'revision_id' => 11,
				'user_id' => 1,
			)),

			self::makeChange( array(
				'id' => 2,
				'type' => 'wikibase-item~update',
				'time' => '20130102020202',
				'object_id' => $entity2,
				'revision_id' => 21,
				'user_id' => 1,
			)),

			self::makeChange( array(
				'id' => 1,
				'type' => 'wikibase-item~update',
				'time' => '20130103030303',
				'object_id' => $entity1,
				'revision_id' => 12,
				'user_id' => 2,
			)),
		);

		return array(
			array( // #0: empty
				array(), // $changes
				array(), // $expectedGroups
			),

			array( // #1: two groups
				$changes, // $changes
				array( // $expectedGroups
					$entity1 => array( $changes[0], $changes[2] ),
					$entity2 => array( $changes[1] ),
				)
			)
		);
	}

	/**
	 * @dataProvider provideGroupChangesByEntity
	 */
	public function testGroupChangesByEntity( $changes, $expectedGroups ) {
		$handler = $this->newChangeHandler();
		$groups = $handler->groupChangesByEntity( $changes );

		$this->assertEquals( count( $expectedGroups ), count( $groups ), "number of groups" );
		$this->assertArrayEquals( array_keys( $expectedGroups ), array_keys( $groups ), false, false );

		foreach ( $groups as $entityId => $group ) {
			$expected = $expectedGroups[$entityId];
			$this->assertArrayEquals( $expected, $group, true, false );
		}
	}

	protected static function getChangeFields( EntityChange $change ) {
		$fields = $change->getFields();
		unset( $fields['id'] );

		return $fields;
	}

	protected function assertChangeEquals( Change $expected, Change $actual, $message = null ) {
		if ( $message ) {
			$message .= ': ';
		} else {
			$message = 'change.';
		}

		$this->assertEquals( get_class( $expected ), get_class( $actual ), $message . "class" );

		$this->assertEquals( $expected->getObjectId(), $actual->getObjectId(), $message . "ObjectId" );
		$this->assertEquals( $expected->getTime(), $actual->getTime(), $message . "Time" );
		$this->assertEquals( $expected->getType(), $actual->getType(), $message . "Type" );
		$this->assertEquals( $expected->getUser(), $actual->getUser(), $message . "User" );

		if ( $expected instanceof EntityChange && $actual instanceof EntityChange ) {
			$this->assertEquals( $expected->getAction(), $actual->getAction(), $message . "Action" );
			$this->assertArrayEquals( $expected->getMetadata(), $actual->getMetadata(), false, true );
		}
	}

	public static function provideMergeChanges() {
		$entity1 = 'q1';

		$change1 = self::makeChange( array(
			'id' => 1,
			'type' => 'wikibase-item~add',
			'time' => '20130101010101',
			'object_id' => $entity1,
			'revision_id' => 11,
			'user_id' => 1,
			'info' => array(
				'metadata' => array (
					'user_text' => 'User1',
					'bot' => 0,
					'page_id' => 1,
					'rev_id' => 11,
					'parent_id' => 0,
					'comment' => 'wikibase-comment-add',
				),
			)
		));

		$change2 = self::makeChange( array(
			'id' => 2,
			'type' => 'wikibase-item~update',
			'time' => '20130102020202',
			'object_id' => $entity1,
			'revision_id' => 12,
			'user_id' => 1,
			'info' => array(
				'metadata' => array (
					'user_text' => 'User1',
					'bot' => 0,
					'page_id' => 1,
					'rev_id' => 12,
					'parent_id' => 11,
					'comment' => 'wikibase-comment-add',
				),
			)
		));

		$change3 = self::makeChange( array(
			'id' => 1,
			'type' => 'wikibase-item~update',
			'time' => '20130103030303',
			'object_id' => $entity1,
			'revision_id' => 13,
			'user_id' => 1,
			'info' => array(
				'metadata' => array (
					'user_text' => 'User1',
					'bot' => 0,
					'page_id' => 1,
					'rev_id' => 13,
					'parent_id' => 12,
					'comment' => 'wikibase-comment-add',
				),
			)
		));

		$change0 = self::makeChange( array(
			'id' => 1,
			'type' => 'wikibase-item~add',
			'time' => '20130101010101',
			'object_id' => $entity1,
			'revision_id' => 0xdeadbeef, // invalid
			'user_id' => 1,
			'info' => array(
				'metadata' => array (
					'user_text' => 'User1',
					'bot' => 0,
					'page_id' => 1,
					'rev_id' => 0xdeadbeef, // invalid
					'parent_id' => 0,
					'comment' => 'wikibase-comment-add',
				),
			)
		));

		$changeMerged = self::makeChange( array(
			'id' => null,
			'type' => 'wikibase-item~add', // because the first change has no parent
			'time' => '20130103030303', // last change's timestamp
			'object_id' => $entity1,
			'revision_id' => 13, // last changes rev id
			'user_id' => 1,
			'info' => array(
				'metadata' => array (
					'user_text' => 'User1',
					'bot' => 0,
					'page_id' => 1,
					'rev_id' => 13,   // rev id from last change
					'parent_id' => 0, // parent rev from first change
					'comment' => 'wikibase-comment-add',
				),
			)
		));

		return array(
			array( // #0: empty
				array(), // $changes
				null, // $expected
			),

			array( // #1: single
				array( $change1 ), // $changes
				$change1, // $expected
			),

			array( // #2: merged
				array( $change1, $change2, $change3 ), // $changes
				$changeMerged, // $expected
			),

			array( // #3: bad
				array( $change0, $change2, $change3 ), // $changes
				null, // $expected
				'MWException', // $error
			),
		);
	}

	/**
	 * @dataProvider provideMergeChanges
	 */
	public function testMergeChanges( $changes, $expected, $error = null ) {
		try {
			$handler = $this->newChangeHandler();
			$merged = $handler->mergeChanges( $changes );

			if ( $error ) {
				$this->fail( "error expected: $error" );
			}

			if ( !$expected ) {
				$this->assertEquals( $expected, $merged );
			} else {
				$this->assertChangeEquals( $expected, $merged );
			}
		} catch ( \MWException $ex ) {
			if ( !$error ) {
				throw $ex;
			}

			$this->assertInstanceOf( $error, $ex, "expected error" );
		}
	}

	/**
	 * @todo: move to TestChanges, unify with TestChanges::getChanges()
	 */
	public static function makeTestChanges( $userId, $numericId ) {
		$prefixedId = 'Q' . $numericId;

		$offset = 100 * $numericId + 1000 * $userId;

		// create with a label and site link set
		$create = self::makeChange( array(
			'id' => $offset + 1,
			'type' => 'wikibase-item~add',
			'time' => '20130101010101',
			'object_id' => $prefixedId,
			'revision_id' => $offset + 11,
			'user_id' => $userId,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'label' => array( 'en' => 'Test' ),
				'links' => array( 'enwiki' => 'Test' ), // old style sitelink representation
			)
		) );

		// set a label
		$update = self::makeChange( array(
			'id' => $offset + 23,
			'type' => 'wikibase-item~update',
			'time' => '20130102020202',
			'object_id' => $prefixedId,
			'revision_id' => $offset + 12,
			'user_id' => $userId,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'label' => array( 'de' => 'Test' ),
			)
		) );

		// merged change consisting of $create and $update
		$create_update = self::makeChange( array(
			'id' => null,
			'type' => $create->getField('type'), // because the first change has no parent
			'time' => $update->getField('time'), // last change's timestamp
			'object_id' => $update->getField('object_id'),
			'revision_id' => $update->getField('revision_id'), // last changes rev id
			'user_id' => $update->getField('user_id'),
			'info' => array(
				'metadata' => array(
					'bot' => 0,
					'comment' => 'wikibase-comment-add' // this assumes a specific 'type'
				)
			)
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'label' => array( 'en' => 'Test', 'de' => 'Test' ),
				'links' => array( 'enwiki' => 'Test' ), // old style sitelink representation
			)
		) );

		// change link to other wiki
		$updateXLink = self::makeChange( array(
			'id' => $offset + 14,
			'type' => 'wikibase-item~update',
			'time' => '20130101020304',
			'object_id' => $prefixedId,
			'revision_id' => $offset + 13,
			'user_id' => $userId,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'links' => array( 'dewiki' => array( 'name' => 'Testen', 'badges' => array() ) ),
			)
		) );

		// merged change consisting of $create, $update and $updateXLink
		$create_update_link = self::makeChange( array(
			'id' => null,
			'type' => $create->getField('type'), // because the first change has no parent
			'time' => $updateXLink->getField('time'), // last change's timestamp
			'object_id' => $updateXLink->getField('object_id'),
			'revision_id' => $updateXLink->getField('revision_id'), // last changes rev id
			'user_id' => $updateXLink->getField('user_id'),
			'info' => array(
				'metadata' => array(
					'bot' => 0,
					'comment' => 'wikibase-comment-add' // this assumes a specific 'type'
				)
			)
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'label' => array( 'en' => 'Test', 'de' => 'Test' ),
				'links' => array(
					'enwiki' => array( 'name' => 'Test' ), // incomplete new style sitelink representation
					'dewiki' => array( 'name' => 'Test' ), // incomplete new style sitelink representation
				),
			)
		) );

		// some other user changed a label
		$updateX = self::makeChange( array(
			'id' => $offset + 12,
			'type' => 'wikibase-item~update',
			'time' => '20130103030303',
			'object_id' => $prefixedId,
			'revision_id' => $offset + 14,
			'user_id' => $userId + 17,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'label' => array( 'fr' => array( 'name' => 'Test', 'badges' => array() ) ),
			)
		) );

		// change link to local wiki
		$updateLink = self::makeChange( array(
			'id' => $offset + 13,
			'type' => 'wikibase-item~update',
			'time' => '20130102030405',
			'object_id' => $prefixedId,
			'revision_id' => $offset + 17,
			'user_id' => $userId,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(
				'links' => array( 'enwiki' => array( 'name' => 'Test', 'badges' => array( 'Q555' ) ) ),
			),
			array(
				'links' => array( 'enwiki' => array( 'name' => 'Spam', 'badges' => array( 'Q12345' ) ) ),
			)
		) );

		// change only badges in link to local wiki
		$updateLinkBadges = self::makeChange( array(
			'id' => $offset + 14,
			'type' => 'wikibase-item~update',
			'time' => '20130102030405',
			'object_id' => $prefixedId,
			'revision_id' => $offset + 18,
			'user_id' => $userId,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(
				'links' => array( 'enwiki' => array( 'name' => 'Test', 'badges' => array( 'Q555' ) ) ),
			),
			array(
				'links' => array( 'enwiki' => array( 'name' => 'Test', 'badges' => array( 'Q12345' ) ) ),
			)
		) );

		// item deleted
		$delete = self::makeChange( array(
			'id' => $offset + 35,
			'type' => 'wikibase-item~remove',
			'time' => '20130105050505',
			'object_id' => $prefixedId,
			'revision_id' => 0,
			'user_id' => $userId,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(
				'label' => array( 'en' => 'Test', 'de' => 'Test' ),
				'links' => array( 'enwiki' => 'Test', 'dewiki' => 'Test' ),
			),
			array()
		) );

		return array(
			'create' => $create,  // create item
			'update' => $update,  // update item
			'create+update' => $create_update, // merged create and update
			'update/other' => $updateX,        // update by another user
			'update-link/local' => $updateLink,  // change the link to this client wiki
			'update-link/local/basges' => $updateLinkBadges,  // change the link to this client wiki
			'update-link/other' => $updateXLink, // change the link to some other client wiki
			'create+update+update-link/other' => $create_update_link, // merged create and update and update link to other wiki
			'delete' => $delete, // delete item
		);
	}

	public static function provideCoalesceRuns() {
		$changes = self::makeTestChanges( 1, 1 );

		$create = $changes['create']; // create item
		$update = $changes['update']; // update item
		$create_update = $changes['create+update']; // merged create and update
		$updateX = $changes['update/other']; // update by another user
		$updateLink = $changes['update-link/local']; // change the link to this client wiki
		$updateXLink = $changes['update-link/other']; // change the link to some other client wiki
		$create_update_link = $changes['create+update+update-link/other']; // merged create and update and update link to other wiki
		$delete = $changes['delete']; // delete item

		return array(
			array( // #0: empty
				array(), // $changes
				array(), // $expected
			),

			array( // #1: single
				array( $create ), // $changes
				array( $create ), // $expected
			),

			array( // #2: create and update
				array( $create, $update ), // $changes
				array( $create_update ), // $expected
			),

			array( // #3: user change
				array( $create, $updateX, $update ), // $changes
				array( $create, $updateX, $update ), // $expected
			),

			array( // #4: action change
				array( $create, $update, $delete ), // $changes
				array( $create_update, $delete ), // $expected
			),

			array( // #5: relevant link manipulation
				array( $create, $updateLink, $update ), // $changes
				array( $create, $updateLink, $update ), // $expected
			),

			array( // #6: irrelevant link manipulation
				array( $create, $update, $updateXLink ), // $changes
				array( $create_update_link ), // $expected
			),
		);
	}

	/**
	 * @dataProvider provideCoalesceRuns
	 */
	public function testCoalesceRuns( $changes, $expected ) {
		$handler = $this->newChangeHandler();
		$coalesced = $handler->coalesceRuns( $changes );

		$this->assertEquals( count( $expected ), count( $coalesced ), "number of changes" );

		$i = 0;
		while ( next( $coalesced ) && next( $expected ) ) {
			$this->assertChangeEquals( current( $expected ), current( $coalesced ), "expected[" . $i++ . "]" );
		}
	}

	public static function provideCoalesceChanges() {
		$changes11 = self::makeTestChanges( 1, 1 );

		$create11 = $changes11['create']; // create item
		$update11 = $changes11['update']; // update item
		$updateXLink11 = $changes11['update-link/other']; // change the link to some other client wiki
		$create_update_link11 = $changes11['create+update+update-link/other']; // merged create and update and update link to other wiki
		$delete11 = $changes11['delete']; // delete item

		$changes12 = self::makeTestChanges( 1, 2 );

		$create12 = $changes12['create']; // create item
		$update12 = $changes12['update']; // update item
		$create_update12 = $changes12['create+update']; // merged create and update

		return array(
			array( // #0: empty
				array(), // $changes
				array(), // $expected
			),

			array( // #1: single
				array( $create11 ), // $changes
				array( $create11 ), // $expected
			),

			array( // #2: unrelated
				array( $create11, $update12 ), // $changes
				array( $create11, $update12 ), // $expected
			),

			array( // #3: reversed
				array( $update12, $create11 ), // $changes
				array( $create11, $update12 ), // $expected
			),

			array( // #4: mixed
				array( $create11, $create12, $update11, $update12, $updateXLink11, $delete11 ), // $changes
				array( $create_update_link11, $create_update12, $delete11 ), // $expected
			),
		);
	}

	/**
	 * @dataProvider provideCoalesceChanges
	 */
	public function testCoalesceChanges( $changes, $expected ) {
		$handler = $this->newChangeHandler();
		$coalesced = $handler->coalesceChanges( $changes );

		$this->assertEquals( count( $expected ), count( $coalesced ), "number of changes" );

		$i = 0;
		while ( next( $coalesced ) && next( $expected ) ) {
			$this->assertChangeEquals( current( $expected ), current( $coalesced ), "expected[" . $i++ . "]" );
		}
	}


	// ==================================================================================

	public static function provideHandleChanges() {
		$empty = Item::newEmpty();
		$empty->setId( new ItemId( 'q55668877' ) );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$itemCreation = $changeFactory->newFromUpdate( EntityChange::ADD, null, $empty );
		$itemDeletion = $changeFactory->newFromUpdate( EntityChange::REMOVE, $empty, null );

		$itemCreation->setField( 'time', '20130101010101' );
		$itemDeletion->setField( 'time', '20130102020202' );

		return array(
			array(),
			array( $itemCreation ),
			array( $itemDeletion ),
			array( $itemCreation, $itemDeletion ),
		);
	}

	/**
	 * @dataProvider provideHandleChanges
	 */
	public function testHandleChanges() {
		global $handleChangeCallCount, $handleChangesCallCount;
		$changes = func_get_args();

		$testHooks = array(
			'WikibaseHandleChange' => array( function( Change $change ) {
				global $handleChangeCallCount;
				$handleChangeCallCount++;
				return true;
			} ),
			'WikibaseHandleChanges' => array( function( array $changes ) {
				global $handleChangesCallCount;
				$handleChangesCallCount++;
				return true;
			} )
		);

		$this->mergeMwGlobalArrayValue( 'wgHooks', $testHooks );

		$handleChangeCallCount = 0;
		$handleChangesCallCount = 0;

		$changeHandler = $this->getMockBuilder( 'Wikibase\ChangeHandler' )
			->disableOriginalConstructor()->setMethods( array( 'coalesceChanges', 'handleChange' ) )->getMock();

		$changeHandler->expects( $this->once() )
			->method( 'coalesceChanges' )->will( $this->returnValue( $changes ) );

		$changeHandler->expects( $this->exactly( count( $changes ) ) )
			->method( 'handleChange' );

		$changeHandler->handleChanges( $changes );

		$this->assertEquals( count( $changes ), $handleChangeCallCount );
		$this->assertEquals( 1, $handleChangesCallCount );

		unset( $handleChangeCallCount );
		unset( $handleChangesCallCount );
	}

	// ==========================================================================================

	public static function provideGetUpdateActions() {
		return array(
			'empty' => array(
				array(),
				array(),
			),
			'sitelink usage' => array( // #1
				array( EntityUsage::SITELINK_USAGE ),
				array( ChangeHandler::LINKS_UPDATE_ACTION, ChangeHandler::WEB_PURGE_ACTION, ChangeHandler::RC_ENTRY_ACTION ),
				array( ChangeHandler::PARSER_PURGE_ACTION )
			),
			'label usage' => array(
				array( EntityUsage::LABEL_USAGE ),
				array( ChangeHandler::PARSER_PURGE_ACTION, ChangeHandler::WEB_PURGE_ACTION, ChangeHandler::RC_ENTRY_ACTION ),
				array( ChangeHandler::LINKS_UPDATE_ACTION )
			),
			'title usage' => array(
				array( EntityUsage::TITLE_USAGE ),
				array( ChangeHandler::PARSER_PURGE_ACTION, ChangeHandler::WEB_PURGE_ACTION, ChangeHandler::RC_ENTRY_ACTION ),
				array( ChangeHandler::LINKS_UPDATE_ACTION )
			),
			'other usage' => array(
				array( EntityUsage::OTHER_USAGE ),
				array( ChangeHandler::PARSER_PURGE_ACTION, ChangeHandler::WEB_PURGE_ACTION, ChangeHandler::RC_ENTRY_ACTION ),
				array( ChangeHandler::LINKS_UPDATE_ACTION )
			),
			'all usage' => array(
				array( EntityUsage::ALL_USAGE ),
				array( ChangeHandler::PARSER_PURGE_ACTION, ChangeHandler::WEB_PURGE_ACTION, ChangeHandler::RC_ENTRY_ACTION ),
				array( ChangeHandler::LINKS_UPDATE_ACTION )
			),
			'sitelink and other usage (no redundant links update)' => array(
				array( EntityUsage::SITELINK_USAGE, EntityUsage::OTHER_USAGE ),
				array( ChangeHandler::PARSER_PURGE_ACTION, ChangeHandler::WEB_PURGE_ACTION, ChangeHandler::RC_ENTRY_ACTION ),
				array( ChangeHandler::LINKS_UPDATE_ACTION )
			),
		);
	}

	/**
	 * @dataProvider provideGetUpdateActions
	 */
	public function testGetUpdateActions( $aspects, $expected, $not = array() ) {
		$handler = $this->newChangeHandler();
		$actions = $handler->getUpdateActions( $aspects );

		sort( $expected );
		sort( $actions );

		// check that $actions contains AT LEAST $expected
		$actual = array_intersect( $actions, $expected );
		$this->assertEquals( array_values( $expected ), array_values( $actual ), "expected actions" );

		$unexpected = array_intersect( $actions, $not );
		$this->assertEmpty( array_values( $unexpected ), "unexpected actions: " . implode( '|', $unexpected ) );
	}

	public static function provideGetEditComment() {
		$changes = TestChanges::getChanges();

		$dummy = \Title::newFromText( "Dummy" );

		return array(
			array( // #0
				$changes['item-deletion-linked'],
				$dummy,
				array( 'q100' => array( 'Emmy' ) ),
				array( 'message' => 'wikibase-comment-remove' )
			),
			array( // #1
				$changes['set-de-label'],
				$dummy,
				array( 'q100' => array( 'Emmy' ) ),
				'set-de-label:1|'
			),
			array( // #2
				$changes['add-claim'],
				$dummy,
				array( 'q100' => array( 'Emmy' ) ),
				'add-claim:1|'
			),
			array( // #3
				$changes['remove-claim'],
				$dummy,
				array( 'q100' => array( 'Emmy' ) ),
				'remove-claim:1|'
			),
			array( // #4
				$changes['set-dewiki-sitelink'],
				$dummy,
				array( 'q100' => array( 'Emmy' ) ),
				array(
					'sitelink' => array(
						'newlink' => array( 'site' => 'dewiki', 'page' => 'Dummy' ),
					),
					'message' => 'wikibase-comment-sitelink-add'
				)
			),
			array( // #5
				$changes['change-dewiki-sitelink'],
				$dummy,
				array( 'q100' => array( 'Emmy' ) ),
				array(
					'sitelink' => array(
						'oldlink' => array( 'site' => 'dewiki', 'page' => 'Dummy' ),
						'newlink' => array( 'site' => 'dewiki', 'page' => 'Dummy2' ),
					),
					'message' => 'wikibase-comment-sitelink-change'
				)
			),
			array( // #6
				$changes['change-enwiki-sitelink'],
				$dummy,
				array( 'q100' => array( 'Emmy' ) ),
				array(
					'sitelink' => array(
						'oldlink' => array( 'site' => 'enwiki', 'page' => 'Emmy' ),
						'newlink' => array( 'site' => 'enwiki', 'page' => 'Emmy2' ),
					),
					'message' => 'wikibase-comment-sitelink-change'
				)
			),
			array( // #7
				$changes['remove-dewiki-sitelink'],
				$dummy,
				array( 'q100' => array( 'Emmy2' ) ),
				array(
					'sitelink' => array(
						'oldlink' => array( 'site' => 'dewiki', 'page' => 'Dummy2' ),
					),
					'message' => 'wikibase-comment-sitelink-remove'
				)
			),
			array( // #8
				$changes['remove-enwiki-sitelink'],
				$dummy,
				array( 'q100' => array( 'Emmy2' ) ),
				array(
					'message' => 'wikibase-comment-unlink'
				)
			),
			array( // #9
				$changes['remove-enwiki-sitelink'],
				$dummy,
				array( 'q100' => array() ),
				array(
					'message' => 'wikibase-comment-unlink'
				)
			),
		);
	}

	/**
	 * Returns a map of fake local page IDs to the corresponding local page names.
	 * The fake page IDs are the IDs of the items that have a sitelink to the
	 * respective page on the local wiki:
	 *
	 * @example if Q100 has a link enwiki => 'Emmy',
	 * then 100 => 'Emmy' will be in the map returned by this method.
	 *
	 * @param array[] $entities Assoc array mapping entity IDs to lists of sitelinks.
	 * This is the form expected by the $entities parameter of testGetPagesToUpdate, etc.
	 *
	 * @return string[]
	 */
	private function getFakePageIdMap( array $entities ) {
		$titlesByPageId = array();
		$siteId = $this->site->getGlobalId();

		foreach ( $entities as $entityKey => $links ) {
			$id = new ItemId( $entityKey );

			// If $links[0] is set, it's considered a link to the local wiki.
			// The index 0 is effectively an alias for $siteId;
			if ( isset( $links[0] ) ) {
				$links[$siteId] = $links[0];
			}

			if ( isset( $links[$siteId] ) ) {
				$pageId = $id->getNumericId();
				$titlesByPageId[$pageId] = $links[$siteId];
			}
		}

		return $titlesByPageId;
	}

	/**
	 * Title factory, using spoofed local page ids that correspond to the ids of items linked to
	 * the respective page (see getUsageLookup).
	 *
	 * @param array[] $entities Assoc array mapping entity IDs to lists of sitelinks.
	 * This is the form expected by the $entities parameter of testGetPagesToUpdate, etc.
	 *
	 * @return TitleFactory
	 */
	private function getTitleFactory( array $entities ) {
		$titlesById = $this->getFakePageIdMap( $entities );
		$pageIdsByTitle = array_flip( $titlesById );

		$titleFactory = $this->getMock( 'Wikibase\Client\Store\TitleFactory' );

		$titleFactory->expects( $this->any() )
			->method( 'newFromID' )
			->will( $this->returnCallback( function( $id ) use ( $titlesById ) {
				if ( isset( $titlesById[$id] ) ) {
					return Title::newFromText( $titlesById[$id] );
				} else {
					throw new StorageException( 'Unknown ID: ' . $id );
				}
			} ) );

		$titleFactory->expects( $this->any() )
			->method( 'newFromText' )
			->will( $this->returnCallback( function( $text, $defaultNs = NS_MAIN ) use ( $pageIdsByTitle ) {
				$title = Title::newFromText( $text, $defaultNs );

				if ( !$title ) {
					throw new StorageException( 'Bad title text: ' . $text );
				}

				if ( isset( $pageIdsByTitle[$text] ) ) {
					$title->resetArticleID( $pageIdsByTitle[$text] );
				} else {
					throw new StorageException( 'Unknown title text: ' . $text );
				}

				return $title;
			} ) );

		return $titleFactory;
	}

	/**
	 * Returns a usage lookup based on $siteLinklookup.
	 * Local page IDs are spoofed using the numeric item ID as the local page ID.
	 *
	 * @param SiteLinkLookup $siteLinklookup
	 *
	 * @return UsageLookup
	 */
	private function getUsageLookup( SiteLinkLookup $siteLinklookup ) {
		$site = $this->site;

		$usageLookup = $this->getMock( 'Wikibase\Client\Usage\UsageLookup' );
		$usageLookup->expects( $this->any() )
			->method( 'getPagesUsing' )
			->will( $this->returnCallback(
				function( $ids ) use ( $siteLinklookup, $site ) {
					$pages = array();

					foreach ( $ids as $id ) {
						$links = $siteLinklookup->getSiteLinksForItem( $id );
						foreach ( $links as $link ) {
							if ( $link->getSiteId() == $site->getGlobalId() ) {
								// we use the numeric item id as the fake page id of the local page!
								$usages = array(
									new EntityUsage( $id, EntityUsage::SITELINK_USAGE ),
									new EntityUsage( $id, EntityUsage::LABEL_USAGE )
								);
								$pages[] = new PageEntityUsages( $id->getNumericId(), $usages );
							}
						}
					}

					return new ArrayIterator( $pages );
				} ) );

		return $usageLookup;
	}

	/**
	 * @return SiteList
	 */
	private function getSiteList() {
		$siteList = $this->getMock( 'SiteList' );
		$siteList->expects( $this->any() )
			->method( 'getSite' )
			->will( $this->returnCallback( function( $globalSiteId ) {
				$site = new \MediaWikiSite();

				$site->setGlobalId( $globalSiteId );
				$site->setLanguageCode( substr( $globalSiteId, 0, 2 ) );

				return $site;
			} ) );

		return $siteList;
	}

	/**
	 * @dataProvider provideGetEditComment
	 */
	public function testGetEditComment( Change $change, \Title $title, $entities, $expected ) {
		$handler = $this->newChangeHandler( null, $entities );
		$comment = $handler->getEditComment( $change, $title );

		if ( is_array( $comment ) && is_array( $expected ) ) {
			$this->assertArrayEquals( $expected, $comment, false, true );
		} else {
			$this->assertEquals( $expected, $comment );
		}
	}

	private function updateMockRepo( MockRepository $repo, $entities ) {
		foreach ( $entities as $id => $siteLinks ) {
			if ( !( $siteLinks instanceof Entity ) ) {
				$entity = Item::newEmpty();
				$entity->setId( new ItemId( $id ) );

				foreach ( $siteLinks as $siteId => $page ) {
					if ( is_int( $siteId ) ) {
						$siteIdentifier = $this->site->getGlobalId();
					} else {
						$siteIdentifier = $siteId;
					}

					$entity->addSiteLink( new SiteLink( $siteIdentifier, $page ) );
				}
			} else {
				$entity = $siteLinks;
			}

			$repo->putEntity( $entity );
		}
	}

	public static function provideHandleChange() {
		$changes = TestChanges::getChanges();

		$empty = array(
			'purgeParserCache' => array(),
			'scheduleRefreshLinks' => array(),
			'purgeWebCache' => array(),
			'injectRCRecord' => array(),
		);

		$emmy2PurgeParser = array(
			'purgeParserCache' => array( 'Emmy2' => true ),
			'scheduleRefreshLinks' => array(),
			'purgeWebCache' => array( 'Emmy2' => true ),
			'injectRCRecord' => array( 'Emmy2' => true ),
		);

		$emmyUpdateLinks = array(
			'purgeParserCache' => array(),
			'scheduleRefreshLinks' => array( 'Emmy' => true ),
			'purgeWebCache' => array( 'Emmy' => true ),
			'injectRCRecord' => array( 'Emmy' => true ),
		);

		$emmy2UpdateLinks = array(
			'purgeParserCache' => array( ),
			'scheduleRefreshLinks' => array( 'Emmy2' => true ),
			'purgeWebCache' => array( 'Emmy2' => true ),
			'injectRCRecord' => array( 'Emmy2' => true ),
		);

		return array(
			array( // #0
				$changes['property-creation'],
				array( 'q100' => array() ),
				$empty
			),
			array( // #1
				$changes['property-deletion'],
				array( 'q100' => array() ),
				$empty
			),
			array( // #2
				$changes['property-set-label'],
				array( 'q100' => array() ),
				$empty
			),

			array( // #3
				$changes['item-creation'],
				array( 'q100' => array() ),
				$empty
			),
			array( // #4
				$changes['item-deletion'],
				array( 'q100' => array() ),
				$empty
			),
			array( // #5
				$changes['item-deletion-linked'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$emmy2PurgeParser
			),

			array( // #6
				$changes['set-de-label'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$empty, // For the dummy page, only label and sitelink usage is defined.
			),
			array( // #7
				$changes['set-en-label'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$emmy2PurgeParser
			),
			array( // #8
				$changes['set-en-label'],
				array( 'q100' => array( 'enwiki' => 'User:Emmy2' ) ), // bad namespace
				$empty
			),
			array( // #9
				$changes['set-en-aliases'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$empty, // For the dummy page, only label and sitelink usage is defined.
			),

			array( // #10
				$changes['add-claim'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$empty // statements are ignored
			),
			array( // #11
				$changes['remove-claim'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$empty // statements are ignored
			),

			array( // #12
				$changes['set-dewiki-sitelink'],
				array( 'q100' => array() ),
				$empty // not yet linked
			),
			array( // #13
				$changes['set-enwiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy' ) ),
				$emmyUpdateLinks
			),

			array( // #14
				$changes['change-dewiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy' ) ),
				$emmyUpdateLinks
			),
			array( // #15
				$changes['change-enwiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy' ), 'q200' => array( 'enwiki' => 'Emmy2' ) ),
				array(
					'purgeParserCache' => array(),
					'scheduleRefreshLinks' => array( 'Emmy' => true, 'Emmy2' => true ),
					'purgeWebCache' => array( 'Emmy' => true, 'Emmy2' => true ),
					'injectRCRecord' => array( 'Emmy' => true, 'Emmy2' => true ),
				)
			),
			array( // #16
				$changes['change-enwiki-sitelink-badges'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$emmy2UpdateLinks
			),

			array( // #17
				$changes['remove-dewiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$emmy2UpdateLinks
			),
			array( // #18
				$changes['remove-enwiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$emmy2UpdateLinks
			),
		);
	}

	/**
	 * @dataProvider provideHandleChange
	 */
	public function testHandleChange( Change $change, $entities, array $expected ) {
		$updater = new MockPageUpdater();
		$handler = $this->newChangeHandler( $updater, $entities );

		$handler->handleChange( $change );
		$updates = $updater->getUpdates();

		$this->assertSameSize( $expected, $updates );

		foreach ( $expected as $k => $exp ) {
			$up = $updates[$k];
			$this->assertEquals( array_keys( $exp ), array_keys( $up ), $k );
		}
	}

}
