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

		return self::$repo;
	}

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

	public static function provideCoalesceRuns() {
		$entity = Item::newEmpty();
		$entity->setId( new EntityId( Item::ENTITY_TYPE, 1 ) );

		// create with a label and site link set
		$create = self::makeChange( array(
			'id' => 1,
			'type' => 'wikibase-item~add',
			'time' => '20130101010101',
			'object_id' => $entity->getId()->getPrefixedId(),
			'revision_id' => 11,
			'user_id' => 1,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'label' => array( 'en' => 'Test' ),
				'sitelinks' => array( 'enwiki' => 'Test' ),
			)
		) );

		// set a label
		$update = self::makeChange( array(
			'id' => 23,
			'type' => 'wikibase-item~update',
			'time' => '20130102020202',
			'object_id' => $entity->getId()->getPrefixedId(),
			'revision_id' => 12,
			'user_id' => 1,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'label' => array( 'de' => 'Test' ),
			)
		) );

		// merged change consisting of $create and $update
		$create_update = self::makeChange( array(
			'id' => null,
			'type' => 'wikibase-item~add', // because the first change has no parent
			'time' => '20130102020202', // last change's timestamp
			'object_id' => $entity->getId()->getPrefixedId(),
			'revision_id' => 12, // last changes rev id
			'user_id' => 1,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'label' => array( 'en' => 'Test', 'de' => 'Test' ),
				'sitelinks' => array( 'enwiki' => 'Test' ),
			)
		) );

		// change link to other wiki
		$updateXLink = self::makeChange( array(
			'id' => 14,
			'type' => 'wikibase-item~update',
			'time' => '20130101020304',
			'object_id' => $entity->getId()->getPrefixedId(),
			'revision_id' => 13,
			'user_id' => 1,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'sitelinks' => array( 'dewiki' => 'Testen' ),
			)
		) );

		// merged change consisting of $create, $update and $updateXLink
		$create_update_link = self::makeChange( array(
			'id' => null,
			'type' => 'wikibase-item~add', // because the first change has no parent
			'time' => '20130101020304', // last change's timestamp
			'object_id' => $entity->getId()->getPrefixedId(),
			'revision_id' => 13, // last changes rev id
			'user_id' => 1,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'label' => array( 'en' => 'Test', 'de' => 'Test' ),
				'sitelinks' => array( 'enwiki' => 'Test', 'dewiki' => 'Test' ),
			)
		) );

		// some other user changed a label
		$updateX = self::makeChange( array(
			'id' => 12,
			'type' => 'wikibase-item~update',
			'time' => '20130103030303',
			'object_id' => $entity->getId()->getPrefixedId(),
			'revision_id' => 14,
			'user_id' => 2,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(),
			array(
				'label' => array( 'fr' => 'Test' ),
			)
		) );

		// change link to local wiki
		$updateLink = self::makeChange( array(
			'id' => 13,
			'type' => 'wikibase-item~update',
			'time' => '20130102030405',
			'object_id' => $entity->getId()->getPrefixedId(),
			'revision_id' => 17,
			'user_id' => 1,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(
				'sitelinks' => array( 'enwiki' => 'Test' ),
			),
			array(
				'sitelinks' => array( 'enwiki' => 'Spam' ),
			)
		) );

		// item deleted
		$delete = self::makeChange( array(
			'id' => 35,
			'type' => 'wikibase-item~remove',
			'time' => '20130105050505',
			'object_id' => $entity->getId()->getPrefixedId(),
			'revision_id' => 0,
			'user_id' => 1,
		), self::makeDiff( Item::ENTITY_TYPE,
			array(
				'label' => array( 'en' => 'Test', 'de' => 'Test' ),
				'sitelinks' => array( 'enwiki' => 'Test', 'dewiki' => 'Test' ),
			),
			array()
		) );

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
				array( $create, $update, $updateLink ), // $changes
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

		while ( next( $changes ) && next( $expected ) ) {
			$this->assertChangeEquals( current( $expected ), current( $changes ) );
		}
	}

}
