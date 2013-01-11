<?php

namespace Wikibase\Test;
use \Wikibase\ChangeHandler;
use \Wikibase\EntityChange;
use \Wikibase\Change;
use \Wikibase\EntityId;
use \Wikibase\EntityDiff;
use \Wikibase\Item;
use \Wikibase\SiteLink;

/**
 * Tests for the ChangeHandler class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ChangeHandlerTest extends \MediaWikiTestCase {


	/** @var MockRepository $repo */
	protected static $repo;

	/** @var ChangeHandler $handler */
	protected $handler;

	public function setUp() {
		parent::setUp();

		$this->handler = new ChangeHandler( self::getMockRepo(), 'enwiki' );
	}

	protected static function getMockRepo() {
		if ( self::$repo ) {
			return self::$repo;
		}

		self::$repo = new MockRepository();

		// entity 1, revision 11
		$entity1 = Item::newEmpty();
		$entity1->setId( new EntityId( Item::ENTITY_TYPE, 1 ) );
		$entity1->setLabel( 'en', 'one' );
		self::$repo->putEntity( $entity1, 11 );

		// entity 1, revision 12
		$entity1->setLabel( 'de', 'eins' );
		self::$repo->putEntity( $entity1, 12 );

		// entity 1, revision 13
		$entity1->setLabel( 'it', 'uno' );
		self::$repo->putEntity( $entity1, 13 );

		// entity 1, revision 1111
		$entity1->setDescription( 'en', 'the first' );
		self::$repo->putEntity( $entity1, 1111 );

		// entity 2, revision 21
		$entity1 = Item::newEmpty();
		$entity1->setId( new EntityId( Item::ENTITY_TYPE, 2 ) );
		$entity1->setLabel( 'en', 'two' );
		self::$repo->putEntity( $entity1, 21 );

		// entity 2, revision 22
		$entity1->setLabel( 'de', 'zwei' );
		self::$repo->putEntity( $entity1, 22 );

		// entity 2, revision 23
		$entity1->setLabel( 'it', 'due' );
		self::$repo->putEntity( $entity1, 23 );

		// entity 2, revision 1211
		$entity1->setDescription( 'en', 'the second' );
		self::$repo->putEntity( $entity1, 1211 );

		return self::$repo;
	}

	/**
	 * @param array                $values
	 * @param \Wikibase\EntityDiff $diff
	 *
	 * @return \Wikibase\EntityChange
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
		$table = \Wikibase\ChangesTable::singleton();
		$change = $table->newRow( $values, true );

		if ( $diff ) {
			$change->setDiff( $diff );
		}

		return $change;
	}

	/*
	public static function makeSiteLink( $siteId, $page ) {
		$site = \Sites::newSite( $siteId );

		$link = new SiteLink( $site, $page );

		return $link;
	}
	*/

	public static function makeDiff( $type, $before, $after ) {
		$differ = new \Diff\MapDiffer( true );

		$diffOps = $differ->doDiff( $before, $after );
		$diff = EntityDiff::newForType( $type, $diffOps );

		return $diff;
	}

	public static function provideGroupChangesByEntity() {
		$entity1 = 'q1';
		$entity2 = 'q2';

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
		$groups = $this->handler->groupChangesByEntity( $changes );

		$this->assertEquals( count( $expectedGroups ), count( $groups ), "number of groups" );
		$this->assertArrayEquals( array_keys( $expectedGroups ), array_keys( $groups ), false, false );

		foreach ( $groups as $entityId => $group ) {
			$expected = $expectedGroups[$entityId];
			$this->assertArrayEquals( $expected, $group, true, false );
		}
	}

	protected static function getChangeFields( Change $change ) {
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

		if ( $expected instanceof EntityChange ) {
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
					'comment' => 'wbc-comment-add',
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
					'comment' => 'wbc-comment-add',
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
					'comment' => 'wbc-comment-add',
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
					'comment' => 'wbc-comment-add',
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
		);
	}

	/**
	 * @dataProvider provideMergeChanges
	 */
	public function testMergeChanges( $changes, $expected ) {
		$merged = $this->handler->mergeChanges( $changes );

		if ( !$expected ) {
			$this->assertEquals( $expected, $merged );
		} else {
			$this->assertChangeEquals( $expected, $merged );
		}
	}

	public static function makeTestChanges( $userId, $entityId ) {
		$entity = Item::newEmpty();
		$entity->setId( new EntityId( Item::ENTITY_TYPE, $entityId ) );

		$offset = 100 * $entityId + 1000 * $userId;

		// create with a label and site link set
		$create = self::makeChange( array(
			'id' => $offset + 1,
			'type' => 'wikibase-item~add',
			'time' => '20130101010101',
			'object_id' => $entity->getId()->getPrefixedId(),
			'revision_id' => $offset + 11,
			'user_id' => $userId,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'label' => array( 'en' => 'Test' ),
				'links' => array( 'enwiki' => 'Test' ),
			)
		) );

		// set a label
		$update = self::makeChange( array(
			'id' => $offset + 23,
			'type' => 'wikibase-item~update',
			'time' => '20130102020202',
			'object_id' => $entity->getId()->getPrefixedId(),
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
					'comment' => 'wbc-comment-add'
				)
			)
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'label' => array( 'en' => 'Test', 'de' => 'Test' ),
				'links' => array( 'enwiki' => 'Test' ),
			)
		) );

		// change link to other wiki
		$updateXLink = self::makeChange( array(
			'id' => $offset + 14,
			'type' => 'wikibase-item~update',
			'time' => '20130101020304',
			'object_id' => $entity->getId()->getPrefixedId(),
			'revision_id' => $offset + 13,
			'user_id' => $userId,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'links' => array( 'dewiki' => 'Testen' ),
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
					'comment' => 'wbc-comment-add'
				)
			)
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'label' => array( 'en' => 'Test', 'de' => 'Test' ),
				'links' => array( 'enwiki' => 'Test', 'dewiki' => 'Test' ),
			)
		) );

		// some other user changed a label
		$updateX = self::makeChange( array(
			'id' => $offset + 12,
			'type' => 'wikibase-item~update',
			'time' => '20130103030303',
			'object_id' => $entity->getId()->getPrefixedId(),
			'revision_id' => $offset + 14,
			'user_id' => $userId + 17,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'label' => array( 'fr' => 'Test' ),
			)
		) );

		// change link to local wiki
		$updateLink = self::makeChange( array(
			'id' => $offset + 13,
			'type' => 'wikibase-item~update',
			'time' => '20130102030405',
			'object_id' => $entity->getId()->getPrefixedId(),
			'revision_id' => $offset + 17,
			'user_id' => $userId,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(
				'links' => array( 'enwiki' => 'Test' ),
			),
			array(
				'links' => array( 'enwiki' => 'Spam' ),
			)
		) );

		// item deleted
		$delete = self::makeChange( array(
			'id' => $offset + 35,
			'type' => 'wikibase-item~remove',
			'time' => '20130105050505',
			'object_id' => $entity->getId()->getPrefixedId(),
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
		$coalesced = $this->handler->coalesceRuns( $changes );

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
		$coalesced = $this->handler->coalesceChanges( $changes );

		$this->assertEquals( count( $expected ), count( $coalesced ), "number of changes" );

		$i = 0;
		while ( next( $coalesced ) && next( $expected ) ) {
			$this->assertChangeEquals( current( $expected ), current( $coalesced ), "expected[" . $i++ . "]" );
		}
	}

}
