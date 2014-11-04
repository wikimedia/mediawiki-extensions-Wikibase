<?php

namespace Wikibase\Client\Tests\Changes;

use Diff\Differ\MapDiffer;
use Wikibase\Change;
use Wikibase\ChangesTable;
use Wikibase\Client\Changes\ChangeRunCoalescer;
use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityChange;
use Wikibase\Test\MockRepository;
use Wikibase\Test\TestChanges;

/**
 * @covers Wikibase\Client\Changes\ChangeRunCoalescer
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
 */
class ChangeRunCoalescerTest extends \MediaWikiTestCase {

	private function newCoalescer( array $entities = array() ) {
		$repo = $this->getMockRepo( $entities );

		$changeFactory = TestChanges::getEntityChangeFactory();

		$coalescer = new ChangeRunCoalescer(
			$repo,
			$changeFactory,
			'enwiki'
		);

		return $coalescer;
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

	/**
	 * @param array $values
	 * @param EntityDiff|null $diff
	 *
	 * @return EntityChange
	 */
	private function makeChange( array $values, EntityDiff $diff = null ) {
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

	private function makeDiff( $type, $before, $after ) {
		$differ = new MapDiffer( true );

		$diffOps = $differ->doDiff( $before, $after );
		$diff = EntityDiff::newForType( $type, $diffOps );

		return $diff;
	}

	private function assertChangeEquals( Change $expected, Change $actual, $message = null ) {
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

	/**
	 * @todo: move to TestChanges, unify with TestChanges::getChanges()
	 */
	private function makeTestChanges( $userId, $numericId ) {
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

	public function provideCoalesceChanges() {
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
		$coalescer = $this->newCoalescer();
		$coalesced = $coalescer->mangleChanges( $changes );

		$this->assertEquals( count( $expected ), count( $coalesced ), "number of changes" );

		$i = 0;
		while ( next( $coalesced ) && next( $expected ) ) {
			$this->assertChangeEquals( current( $expected ), current( $coalesced ), "expected[" . $i++ . "]" );
		}
	}

}
