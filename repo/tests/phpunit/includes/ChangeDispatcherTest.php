<?php

namespace Wikibase\Test;

use Wikibase\Change;
use Wikibase\ChunkAccess;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\ChangeDispatcher;
use Wikibase\Repo\Notifications\ChangeNotificationSender;
use Wikibase\Store\ChangeDispatchCoordinator;
use Wikibase\Store\SubscriptionLookup;

/**
 * @covers Wikibase\Repo\ChangeDispatcher
 *
 * @group Wikibase
 * @group WikibaseChange
 * @group WikibaseChangeDispatcher
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ChangeDispatcherTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var array[]
	 */
	private $subscriptions;

	/**
	 * @var Change[]
	 */
	private $changes;

	/**
	 * @param ChangeDispatchCoordinator $coordinator
	 * @param array[] &$notifications
	 *
	 * @return ChangeDispatcher
	 */
	private function getChangeDispatcher( ChangeDispatchCoordinator $coordinator, &$notifications = array() ) {
		$dispatcher = new ChangeDispatcher(
			$coordinator,
			$this->getNotificationSender( $notifications ),
			$this->getChunkedChangesAccess(),
			$this->getSubscriptionLookup()
		);

		return $dispatcher;
	}

	/**
	 * @param array[] &$notifications An array to receive any notifications,
	 *                each having the form array( $siteID, $changes ).
	 *
	 * @return ChangeNotificationSender
	 */
	private function getNotificationSender( &$notifications = array() ) {
		$sender = $this->getMock( 'Wikibase\Repo\Notifications\ChangeNotificationSender' );

		$sender->expects( $this->any() )
			->method( 'sendNotification' )
			->will(  $this->returnCallback( function ( $siteID, array $changes ) use ( &$notifications ) {
				$notifications[] = array( $siteID, $changes );
			} ) );

		return $sender;
	}

	/**
	 * @return ChunkAccess<Change>
	 */
	private function getChunkedChangesAccess() {
		$chunkedAccess = $this->getMock( 'Wikibase\ChunkAccess' );

		$chunkedAccess->expects( $this->any() )
			->method( 'loadChunk' )
			->will( $this->returnCallback( array( $this, 'getChanges' ) ) );

		$chunkedAccess->expects( $this->any() )
			->method( 'getRecordId' )
			->will( $this->returnCallback( function ( Change $change ) {
				return $change->getId();
			} ) );

		return $chunkedAccess;
	}

	/**
	 * @return SubscriptionLookup
	 */
	private function getSubscriptionLookup() {
		$lookup = $this->getMock( 'Wikibase\Store\SubscriptionLookup' );

		$lookup->expects( $this->any() )
			->method( 'getSubscriptions' )
			->will( $this->returnCallback( array( $this, 'getSubscriptions' ) ) );

		return $lookup;
	}

	public function getChanges( $fromId, $limit ) {
		return array_slice( $this->changes, max( $fromId, 1 ), $limit );
	}

	public function getSubscriptions( $siteId, array $entityIds ) {
		if ( !isset( $this->subscriptions[$siteId] ) ) {
			return array();
		}

		return array_intersect( $this->subscriptions[$siteId], $entityIds );
	}

	/**
	 * @return Change[]
	 */
	private function getAllChanges() {
		$changeId = 1;
		return array(
			null, // skip index 0
			$this->newChange( $changeId++, 'Q11' ),
			$this->newChange( $changeId++, 'Q11' ),
			$this->newChange( $changeId++, 'Q22' ),
			$this->newChange( $changeId++, 'Q22' ),
			$this->newChange( $changeId++, 'Q33' ),
			$this->newChange( $changeId++, 'Q33' ),
			$this->newChange( $changeId++, 'Q44' ),
			$this->newChange( $changeId++, 'Q44' ),
		);
	}

	public function setUp() {
		$this->subscriptions['enwiki'] = array(
			new ItemId( 'Q11' ),
			new ItemId( 'Q22' ),
			new ItemId( 'Q33' ),
		);

		$this->subscriptions['dewiki'] = array(
			new ItemId( 'Q22' ),
			new ItemId( 'Q44' ),
		);

		$this->changes = $this->getAllChanges();
	}

	/**
	 * @param int $id
	 * @param string $objectId
	 *
	 * @return Change
	 */
	private function newChange( $id, $objectId ) {
		$change = $this->getMock( 'Wikibase\Change' );

		$change->expects( $this->any() )
			->method( 'getId' )
			->will(  $this->returnValue( $id ) );

		$change->expects( $this->any() )
			->method( 'getObjectId' )
			->will(  $this->returnValue( $objectId ) );

		return $change;
	}

	public function testSelectClient() {
		$siteId = 'testwiki';

		$expectedClientState = array(
			'chd_site' =>   $siteId,
			'chd_db' =>     $siteId,
			'chd_seen' =>   0,
			'chd_touched' => '20140303000000',
			'chd_lock' =>   null
		);

		$coordinator = $this->getMock( 'Wikibase\Store\ChangeDispatchCoordinator' );

		$coordinator->expects( $this->once() )
			->method( 'selectClient' )
			->will(  $this->returnValue( $expectedClientState ) );

		$coordinator->expects( $this->never() )
			->method( 'initState' );

		$dispatcher = $this->getChangeDispatcher( $coordinator );

		// This does nothing but call $coordinator->selectClient()
		$actualClientState = $dispatcher->selectClient();
		$this->assertEquals( $expectedClientState, $actualClientState );
	}

	public function provideGetPendingChanges() {
		$changes = $this->getAllChanges();

		return array(
			array( 'enwiki', 0, 3, 1, array( $changes[1], $changes[2], $changes[3] ), 3 ),
			//FIXME: test more!
		);
	}

	/**
	 * @dataProvider provideGetPendingChanges
	 */
	public function testGetPendingChanges( $siteId, $afterId, $batchSize, $batchChunkFactor, $expectedChanges, $expectedSeen ) {
		$coordinator = $this->getMock( 'Wikibase\Store\ChangeDispatchCoordinator' );

		$dispatcher = $this->getChangeDispatcher( $coordinator );

		$dispatcher->setBatchSize( $batchSize );
		$dispatcher->setBatchChunkFactor( $batchChunkFactor );

		$pending = $dispatcher->getPendingChanges(
			$siteId,
			$afterId
		);

		$this->assertSameChanges( $expectedChanges, $pending[0] );
		$this->assertEquals( $expectedSeen, $pending[1] );
	}

	public function provideSelectDispatchTo() {

		return array(
			array(
				array(
					'chd_site' =>   'enwiki',
					'chd_db' =>     'enwikidb',
					'chd_seen' =>   0,
					'chd_touched' => '20140303000000',
					'chd_lock' =>   null
				),
				array(
					'chd_site' =>   'enwiki',
					'chd_db' =>     'enwikidb',
					'chd_seen' =>   3,
					'chd_touched' => '20140303000011',
					'chd_lock' =>   null
				),
				3,
				array(
					array( 'repowiki', array( 1, 2, 3 ) )
				)
			),
			//FIXME: test more!
		);
	}

	/**
	 * @dataProvider provideSelectDispatchTo
	 */
	public function testSelectDispatchTo( $wikiState, $expectedFinalState, $expectedFinalSeen, $expectedNotifications ) {
		$coordinator = $this->getMock( 'Wikibase\Store\ChangeDispatchCoordinator' );

		$coordinator->expects( $this->once() )
			->method( 'lockClient' )
			->with( $wikiState['chd_site'] );

		$coordinator->expects( $this->once() )
			->method( 'releaseClient' )
			->with( $expectedFinalSeen, $expectedFinalState );

		$dispatcher = $this->getChangeDispatcher(
			$coordinator,
			$notifications
		);

		$dispatcher->setBatchSize( 2 );

		$dispatcher->dispatchTo(
			$wikiState
		);

		$this->assertEquals( $expectedNotifications, $notifications );
	}

	private function assertSameChanges( $expected, $actual ) {
		$getId = function( Change $change ) {
			return $change->getId();
		};

		$expected = array_map( $getId, $expected );
		$actual = array_map( $getId, $actual );

		$this->assertEquals( $expected, $actual );
	}

}
