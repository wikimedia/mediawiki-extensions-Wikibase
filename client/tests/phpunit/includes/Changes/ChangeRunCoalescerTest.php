<?php

namespace Wikibase\Client\Tests\Changes;

use Diff\DiffOp\AtomicDiffOp;
use Traversable;
use Wikibase\Change;
use Wikibase\Client\Changes\ChangeRunCoalescer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\ItemDiffer;
use Wikibase\DataModel\SiteLink;
use Wikibase\EntityChange;
use Wikibase\ItemChange;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Lib\Tests\Changes\TestChanges;

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
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ChangeRunCoalescerTest extends \MediaWikiTestCase {

	private function getChangeRunCoalescer() {
		$entityRevisionLookup = $this->getEntityRevisionLookup();
		$changeFactory = TestChanges::getEntityChangeFactory();

		$coalescer = new ChangeRunCoalescer(
			$entityRevisionLookup,
			$changeFactory,
			'enwiki'
		);

		return $coalescer;
	}

	/**
	 * @return EntityRevisionLookup
	 */
	private function getEntityRevisionLookup() {
		$repo = new MockRepository();

		$offsets = array( 'Q1' => 1100, 'Q2' => 1200 );
		foreach ( $offsets as $qid => $offset ) {
			// entity 1, revision 1111
			$entity1 = new Item( new ItemId( $qid ) );
			$entity1->setLabel( 'en', 'ORIGINAL' );
			$entity1->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Original' );
			$repo->putEntity( $entity1, $offset + 11 );

			// entity 1, revision 1112
			$entity1->setLabel( 'de', 'HINZUGEFÜGT' );
			$repo->putEntity( $entity1, $offset + 12 );

			// entity 1, revision 1113
			$entity1->setLabel( 'nl', 'Addiert' );
			$repo->putEntity( $entity1, $offset + 13 );

			// entity 1, revision 1114
			$entity1->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Testen' );
			$repo->putEntity( $entity1, $offset + 14 );

			// entity 1, revision 1117
			$entity1->getSiteLinkList()->setSiteLink( new SiteLink( 'enwiki', 'Spam', array( new ItemId( 'Q12345' ) ) ) );
			$repo->putEntity( $entity1, $offset + 17 );

			// entity 1, revision 1118
			$entity1->getSiteLinkList()->setSiteLink( new SiteLink( 'enwiki', 'Spam', array( new ItemId( 'Q54321' ) ) ) );
			$repo->putEntity( $entity1, $offset + 18 );
		}

		return $repo;
	}

	/**
	 * @param array $values
	 *
	 * @return EntityChange
	 */
	private function makeChange( array $values ) {
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
			$values['info']['metadata']['user_text'] = 'User' . $values['user_id'];
		}

		if ( !isset( $values['info']['metadata']['parent_id'] ) && isset( $values['parent_id'] ) ) {
			$values['info']['metadata']['parent_id'] = $values['parent_id'];
		}

		if ( !isset( $values['info']['metadata']['parent_id'] ) ) {
			$values['info']['metadata']['parent_id'] = 0;
		}

		if ( !isset( $values['info']['metadata']['comment'] ) && isset( $values['comment'] ) ) {
			$values['info']['metadata']['comment'] = $values['comment'];
		}

		if ( !isset( $values['info']['metadata']['comment'] ) ) {
			$values['info']['metadata']['comment'] = str_replace( '~', '-', $values['type'] );
		}

		$diff = $this->makeDiff( $values['object_id'], $values['info']['metadata']['parent_id'], $values[ 'revision_id' ] );
		$values['info'] = serialize( $values['info'] );

		if ( $values['type'] === 'wikibase-item~add' || $values['type'] === 'wikibase-item~update' ) {
			$change = new ItemChange( $values );
		} else {
			$change = new EntityChange( $values );
		}
		$change->setEntityId( new ItemId( $values['object_id'] ) );

		$change->setDiff( $diff );

		return $change;
	}

	private function combineChanges( EntityChange $first, EntityChange $last ) {
		$firstmeta = $first->getMetadata();
		$lastmeta = $last->getMetadata();

		return $this->makeChange( array(
			'id' => null,
			'type' => $first->getField( 'type' ), // because the first change has no parent
			'time' => $last->getField( 'time' ), // last change's timestamp
			'object_id' => $last->getField( 'object_id' ),
			'revision_id' => $last->getField( 'revision_id' ), // last changes rev id
			'user_id' => $last->getField( 'user_id' ),
			'info' => array(
				'metadata' => array(
					'bot' => 0,
					'comment' => $lastmeta['comment'],
					'parent_id' => $firstmeta['parent_id'],
				)
			)
		) );
	}

	private function makeDiff( $objectId, $revA, $revB ) {
		$lookup = $this->getEntityRevisionLookup();

		$itemId = new ItemId( $objectId );

		if ( $revA === 0 ) {
			$oldEntity = new Item();
		} else {
			$oldEntity = $lookup->getEntityRevision( $itemId, $revA )->getEntity();
		}

		if ( $revB === 0 ) {
			$newEntity = new Item();
		} else {
			$newEntity = $lookup->getEntityRevision( $itemId, $revB )->getEntity();
		}

		$differ = new ItemDiffer();
		return $differ->diffEntities( $oldEntity, $newEntity );
	}

	private function assertChangeEquals( Change $expected, Change $actual ) {
		$this->assertEquals( get_class( $expected ), get_class( $actual ), 'change.class' );

		$this->assertEquals( $expected->getObjectId(), $actual->getObjectId(), 'change.ObjectId' );
		$this->assertEquals( $expected->getTime(), $actual->getTime(), 'change.Time' );
		$this->assertEquals( $expected->getType(), $actual->getType(), 'change.Type' );

		if ( $expected instanceof EntityChange && $actual instanceof EntityChange ) {
			$this->assertEquals( $expected->getAction(), $actual->getAction(), 'change.Action' );
			$this->assertArrayEquals( $expected->getMetadata(), $actual->getMetadata(), false, true );
		}

		$this->assertDiffsEqual( $expected->getDiff(), $actual->getDiff() );
	}

	private function assertDiffsEqual( $expected, $actual, $path = '' ) {
		if ( $expected instanceof AtomicDiffOp ) {
			//$this->assertEquals( $expected->getType(), $actual->getType(), $path . ' DiffOp.type' );
			$this->assertEquals( serialize( $expected ), serialize( $actual ), $path . ' DiffOp' );
			return;
		}

		if ( $expected instanceof Traversable ) {
			$expected = iterator_to_array( $expected );
			$actual = iterator_to_array( $actual );
		}

		foreach ( $expected as $key => $expectedValue ) {
			$currentPath = "$path/$key";
			$this->assertArrayHasKey( $key, $actual, $currentPath . " missing key" );
			$this->assertDiffsEqual( $expectedValue, $actual[$key], $currentPath );
		}

		$extraKeys = array_diff( array_keys( $actual ), array_keys( $expected ) );
		$this->assertEquals( array(), $extraKeys, $path . " extra keys" );
	}

	public function provideCoalesceChanges() {
		$id = 0;

		// create with a label and site link set
		$create11 = $this->makeChange( array(
			'id' => ++$id,
			'type' => 'wikibase-item~add',
			'time' => '20130101010101',
			'object_id' => 'Q1',
			'revision_id' => 1111,
			'user_id' => 1,
		) );

		// set a label
		$update11 = $this->makeChange( array(
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130102020202',
			'object_id' => 'Q1',
			'revision_id' => 1112,
			'user_id' => 1,
			'parent_id' => 1111,
		) );

		// set another label
		$anotherUpdate11 = $this->makeChange( array(
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130102020203',
			'object_id' => 'Q1',
			'revision_id' => 1113,
			'user_id' => 1,
			'parent_id' => 1112,
		) );

		// set another label, by another user
		$anotherUpdate21 = $this->makeChange( array(
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130102020203',
			'object_id' => 'Q1',
			'revision_id' => 1113,
			'user_id' => 2,
			'parent_id' => 1112,
		) );

		// change link to other wiki
		$update11XLink = $this->makeChange( array(
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130101020304',
			'object_id' => 'Q1',
			'revision_id' => 1114,
			'user_id' => 1,
			'parent_id' => 1113,
		) );

		// change link to local wiki
		$update11Link = $this->makeChange( array(
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130102030407',
			'object_id' => 'Q1',
			'revision_id' => 1117,
			'user_id' => 1,
			'parent_id' => 1114,
		) );

		// delete
		$delete11 = $this->makeChange( array(
			'id' => ++$id,
			'type' => 'wikibase-item~remove',
			'time' => '20130102030409',
			'object_id' => 'Q1',
			'revision_id' => 0,
			'user_id' => 1,
			'parent_id' => 1118,
		) );

		// set a label
		$update12 = $this->makeChange( array(
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130102020102',
			'object_id' => 'Q2',
			'revision_id' => 1212,
			'user_id' => 1,
			'parent_id' => 1211,
		) );

		// set another label
		$anotherUpdate12 = $this->makeChange( array(
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130102020303',
			'object_id' => 'Q2',
			'revision_id' => 1213,
			'user_id' => 1,
			'parent_id' => 1213,
		) );

		return array(
			'empty' => array(
				array(), // $changes
				array(), // $expected
			),

			'single' => array(
				array( $create11 ), // $changes
				array( $create11 ), // $expected
			),

			'simple run' => array(
				array( $update11, $anotherUpdate11 ), // $changes
				array( $this->combineChanges( $update11, $anotherUpdate11 ) ), // $expected
			),

			'long run' => array( // create counts as update, delete doesn't
				array( $create11, $update11, $anotherUpdate11 ), // $changes
				array( $this->combineChanges( $create11, $anotherUpdate11 ) ), // $expected
			),

			'different items' => array(
				array( $update11, $anotherUpdate12 ), // $changes
				array( $update11, $anotherUpdate12 ), // $changes
			),

			'different users' => array(
				array( $update11, $anotherUpdate21 ), // $changes
				array( $update11, $anotherUpdate21 ), // $changes
			),

			'reversed' => array( // result is sorted by timestamp
				array( $update12, $create11 ), // $changes
				array( $create11, $update12 ), // $expected
			),

			'mingled' => array(
				array( $update12, $update11, $anotherUpdate11, $anotherUpdate12 ), // $changes
				array( // result is sorted by timestamp
					$this->combineChanges( $update11, $anotherUpdate11 ),
					$this->combineChanges( $update12, $anotherUpdate12 ),
				), // $expected
			),

			'different action' => array( // create counts as update, delete doesn't
				array( $update11, $delete11 ), // $changes
				array( $update11, $delete11 ), // $expected
			),

			'local link breaks' => array(
				array( $update11, $update11Link ), // $changes
				array( $update11, $update11Link ), // $expected
			),

			'other link merges' => array(
				array( $update11, $update11XLink ), // $changes
				array( $this->combineChanges( $update11, $update11XLink ) ), // $expected
			),
		);
	}

	/**
	 * @dataProvider provideCoalesceChanges
	 */
	public function testCoalesceChanges( $changes, $expected ) {
		$coalescer = $this->getChangeRunCoalescer();
		$coalesced = $coalescer->transformChangeList( $changes );

		$this->assertEquals( $this->getChangeIds( $expected ), $this->getChangeIds( $coalesced ) );

		// We know the arrays have the same length, but know nothing about they keys.
		$expected = array_values( $expected );
		$coalesced = array_values( $coalesced );

		foreach ( $expected as $i => $expectedChange ) {
			$actualChange = $coalesced[$i];
			$this->assertChangeEquals( $expectedChange, $actualChange );
		}
	}

	private function getChangeIds( array $changes ) {
		return array_map(
			function( Change $change ) {
				return $change->getId();
			},
			$changes
		);
	}

}
