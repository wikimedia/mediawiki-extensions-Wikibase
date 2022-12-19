<?php

namespace Wikibase\Client\Tests\Integration\Changes;

use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;
use Wikibase\Client\Changes\ChangeRunCoalescer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\ItemDiffer;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Changes\Change;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityDiffChangedAspectsFactory;
use Wikibase\Lib\Changes\ItemChange;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Tests\Changes\TestChanges;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers \Wikibase\Client\Changes\ChangeRunCoalescer
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseChange
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChangeRunCoalescerTest extends MediaWikiIntegrationTestCase {

	private function getChangeRunCoalescer() {
		$entityRevisionLookup = $this->getEntityRevisionLookup();
		$changeFactory = TestChanges::getEntityChangeFactory();

		$coalescer = new ChangeRunCoalescer(
			$entityRevisionLookup,
			$changeFactory,
			new NullLogger(),
			'enwiki'
		);

		return $coalescer;
	}

	/**
	 * @return EntityRevisionLookup
	 */
	private function getEntityRevisionLookup() {
		$repo = new MockRepository();

		$offsets = [ 'Q1' => 1100, 'Q2' => 1200 ];
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

			// entity 1, revision 1115
			$entity1->getSiteLinkList()->setSiteLink( new SiteLink( 'enwiki', 'Original', [ new ItemId( 'Q12345' ) ] ) );
			$repo->putEntity( $entity1, $offset + 15 );

			// entity 1, revision 1117
			$entity1->getSiteLinkList()->setSiteLink( new SiteLink( 'enwiki', 'Spam', [ new ItemId( 'Q12345' ) ] ) );
			$repo->putEntity( $entity1, $offset + 17 );

			// entity 1, revision 1118
			$entity1->getSiteLinkList()->setSiteLink( new SiteLink( 'enwiki', 'Spam', [ new ItemId( 'Q54321' ) ] ) );
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
			$values['info'] = [];
		}

		if ( !isset( $values['info']['metadata'] ) ) {
			$values['info']['metadata'] = [];
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
		$values['info'] = json_encode( $values['info'] );

		if ( $values['type'] === 'wikibase-item~add' || $values['type'] === 'wikibase-item~update' ) {
			$change = new ItemChange( $values );
		} else {
			$change = new EntityChange( $values );
		}
		$change->setEntityId( new ItemId( $values['object_id'] ) );

		$diffAspects = ( new EntityDiffChangedAspectsFactory() )->newFromEntityDiff( $diff );
		$change->setCompactDiff( $diffAspects );

		return $change;
	}

	private function combineChanges( EntityChange $first, EntityChange $last ) {
		$firstmeta = $first->getMetadata();
		$lastmeta = $last->getMetadata();

		return $this->makeChange( [
			'id' => null,
			'type' => $first->getField( 'type' ), // because the first change has no parent
			'time' => $last->getField( 'time' ), // last change's timestamp
			'object_id' => $last->getField( 'object_id' ),
			'revision_id' => $last->getField( 'revision_id' ), // last changes rev id
			'user_id' => $last->getField( 'user_id' ),
			'info' => [
				'metadata' => [
					'bot' => 0,
					'comment' => $lastmeta['comment'],
					'parent_id' => $firstmeta['parent_id'],
				],
			],
		] );
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

		$this->assertSame(
			$expected->getCompactDiff()->toArray(),
			$actual->getCompactDiff()->toArray()
		);
	}

	public function provideCoalesceChanges() {
		$id = 0;

		// create with a label and site link set
		$create11 = $this->makeChange( [
			'id' => ++$id,
			'type' => 'wikibase-item~add',
			'time' => '20130101010101',
			'object_id' => 'Q1',
			'revision_id' => 1111,
			'user_id' => 1,
		] );

		// set a label
		$update11 = $this->makeChange( [
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130102020202',
			'object_id' => 'Q1',
			'revision_id' => 1112,
			'user_id' => 1,
			'parent_id' => 1111,
		] );

		// set another label
		$anotherUpdate11 = $this->makeChange( [
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130102020203',
			'object_id' => 'Q1',
			'revision_id' => 1113,
			'user_id' => 1,
			'parent_id' => 1112,
		] );

		// set another label, by another user
		$anotherUpdate21 = $this->makeChange( [
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130102020203',
			'object_id' => 'Q1',
			'revision_id' => 1113,
			'user_id' => 2,
			'parent_id' => 1112,
		] );

		// change link to other wiki
		$update11XLink = $this->makeChange( [
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130101020304',
			'object_id' => 'Q1',
			'revision_id' => 1114,
			'user_id' => 1,
			'parent_id' => 1113,
		] );

		// change link to other wiki
		$update11Badge = $this->makeChange( [
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130101020305',
			'object_id' => 'Q1',
			'revision_id' => 1115,
			'user_id' => 1,
			'parent_id' => 1114,
		] );

		// change link to local wiki
		$update11Link = $this->makeChange( [
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130102030407',
			'object_id' => 'Q1',
			'revision_id' => 1117,
			'user_id' => 1,
			'parent_id' => 1115,
		] );

		// delete
		$delete11 = $this->makeChange( [
			'id' => ++$id,
			'type' => 'wikibase-item~remove',
			'time' => '20130102030409',
			'object_id' => 'Q1',
			'revision_id' => 0,
			'user_id' => 1,
			'parent_id' => 1118,
		] );

		// set a label
		$update12 = $this->makeChange( [
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130102020102',
			'object_id' => 'Q2',
			'revision_id' => 1212,
			'user_id' => 1,
			'parent_id' => 1211,
		] );

		// set another label
		$anotherUpdate12 = $this->makeChange( [
			'id' => ++$id,
			'type' => 'wikibase-item~update',
			'time' => '20130102020303',
			'object_id' => 'Q2',
			'revision_id' => 1213,
			'user_id' => 1,
			'parent_id' => 1213,
		] );

		return [
			'empty' => [
				[], // $changes
				[], // $expected
			],

			'single' => [
				[ $create11 ], // $changes
				[ $create11 ], // $expected
			],

			'simple run' => [
				[ $update11, $anotherUpdate11 ], // $changes
				[ $this->combineChanges( $update11, $anotherUpdate11 ) ], // $expected
			],

			'long run' => [ // create counts as update, delete doesn't
				[ $create11, $update11, $anotherUpdate11 ], // $changes
				[ $this->combineChanges( $create11, $anotherUpdate11 ) ], // $expected
			],

			'different items' => [
				[ $update11, $anotherUpdate12 ], // $changes
				[ $update11, $anotherUpdate12 ], // $changes
			],

			'different users' => [
				[ $update11, $anotherUpdate21 ], // $changes
				[ $update11, $anotherUpdate21 ], // $changes
			],

			'reversed' => [ // result is sorted by timestamp
				[ $update12, $create11 ], // $changes
				[ $create11, $update12 ], // $expected
			],

			'mingled' => [
				[ $update12, $update11, $anotherUpdate11, $anotherUpdate12 ], // $changes
				[ // result is sorted by timestamp
					$this->combineChanges( $update11, $anotherUpdate11 ),
					$this->combineChanges( $update12, $anotherUpdate12 ),
				], // $expected
			],

			'different action' => [ // create counts as update, delete doesn't
				[ $update11, $delete11 ], // $changes
				[ $update11, $delete11 ], // $expected
			],

			'local link breaks' => [
				[ $update11, $update11Link ], // $changes
				[ $update11, $update11Link ], // $expected
			],

			'local link badge change' => [
				[ $update11, $update11Badge ], // $changes
				[ $this->combineChanges( $update11, $update11Badge ) ], // $expected
			],

			'other link merges' => [
				[ $update11, $update11XLink ], // $changes
				[ $this->combineChanges( $update11, $update11XLink ) ], // $expected
			],
		];
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
