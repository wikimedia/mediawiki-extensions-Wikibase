<?php

namespace Wikibase\Repo\Tests;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\Diff\MapDiff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use PHPUnit4And6Compat;
use PHPUnit_Framework_MockObject_Matcher_Invocation;
use Wikibase\Change;
use Wikibase\Lib\Store\ChunkAccess;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityChange;
use Wikibase\ItemChange;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\NullMessageReporter;
use Wikibase\Repo\ChangeDispatcher;
use Wikibase\Repo\Notifications\ChangeNotificationSender;
use Wikibase\Store\ChangeDispatchCoordinator;
use Wikibase\Store\SubscriptionLookup;

/**
 * @covers Wikibase\Repo\ChangeDispatcher
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChangeDispatcherTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @var array[]
	 */
	private $subscriptions;

	/**
	 * @var Change[]
	 */
	private $changes;

	/**
	 * @var string
	 */
	private $now = '20140303021010';

	/**
	 * @param ChangeDispatchCoordinator $coordinator
	 * @param array[] &$notifications
	 *
	 * @return ChangeDispatcher
	 */
	private function getChangeDispatcher( ChangeDispatchCoordinator $coordinator, array &$notifications = [] ) {
		$dispatcher = new ChangeDispatcher(
			$coordinator,
			$this->getNotificationSender( $notifications ),
			$this->getChunkedChangesAccess( $this->any() ),
			$this->getSubscriptionLookup()
		);

		return $dispatcher;
	}

	/**
	 * @param array[] &$notifications An array to receive any notifications,
	 *                each having the form [ $siteID, $changes ].
	 *
	 * @return ChangeNotificationSender
	 */
	private function getNotificationSender( array &$notifications = [] ) {
		$sender = $this->getMock( ChangeNotificationSender::class );

		$sender->expects( $this->any() )
			->method( 'sendNotification' )
			->will( $this->returnCallback( function ( $siteID, array $changes ) use ( &$notifications ) {
				$notifications[] = [ $siteID, $changes ];
			} ) );

		return $sender;
	}

	/**
	 * @param PHPUnit_Framework_MockObject_Matcher_Invocation|null $expectedLoadChunkCalls
	 *
	 * @return ChunkAccess Guaranteed to only return Change objects from loadChunk.
	 */
	private function getChunkedChangesAccess( $expectedLoadChunkCalls = null ) {
		$chunkedAccess = $this->getMock( ChunkAccess::class );

		$chunkedAccess->expects( $expectedLoadChunkCalls ?: $this->never() )
			->method( 'loadChunk' )
			->will( $this->returnCallback( function ( $fromId, $limit ) {
				return array_slice( $this->changes, $fromId, $limit );
			} ) );

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
		$lookup = $this->getMock( SubscriptionLookup::class );

		$lookup->expects( $this->any() )
			->method( 'getSubscriptions' )
			->will( $this->returnCallback( function ( $siteId, array $entityIds ) {
				return isset( $this->subscriptions[$siteId] )
					? array_intersect( $this->subscriptions[$siteId], $entityIds )
					: [];
			} ) );

		return $lookup;
	}

	/**
	 * @return Change[]
	 */
	private function getAllChanges() {
		$changeId = 0;

		$addEn = new MapDiff( [ 'enwiki' => new DiffOpAdd( 'Foo' ) ] );
		$changeEn = new MapDiff( [ 'enwiki' => new DiffOpChange( 'Foo', 'Bar' ) ] );

		$addDe = new MapDiff( [ 'dewiki' => new DiffOpAdd( 'Fuh' ) ] );
		$removeDe = new MapDiff( [ 'dewiki' => new DiffOpRemove( 'Fuh' ) ] );

		return [
			// index 0 is ignored, or used as the base change.
			$this->newChange( 0, new ItemId( 'Q99999' ), sprintf( '201403030100', 0 ) ),
			$this->newChange( ++$changeId, new PropertyId( 'P11' ), sprintf( '2014030301%02d', $changeId ) ),
			$this->newChange( ++$changeId, new PropertyId( 'P11' ), sprintf( '2014030301%02d', $changeId ) ),
			$this->newChange( ++$changeId, new ItemId( 'Q22' ), sprintf( '2014030301%02d', $changeId ) ),
			$this->newChange( ++$changeId, new ItemId( 'Q22' ), sprintf( '2014030301%02d', $changeId ) ),
			$this->newChange( ++$changeId, new ItemId( 'Q33' ), sprintf( '2014030301%02d', $changeId ), $addEn ),
			$this->newChange( ++$changeId, new ItemId( 'Q33' ), sprintf( '2014030301%02d', $changeId ), $changeEn ),
			$this->newChange( ++$changeId, new ItemId( 'Q44' ), sprintf( '2014030301%02d', $changeId ), $addDe ),
			$this->newChange( ++$changeId, new ItemId( 'Q44' ), sprintf( '2014030301%02d', $changeId ), $removeDe ),
		];
	}

	protected function setUp() {
		$this->subscriptions['enwiki'] = [
			new PropertyId( 'P11' ),
			new ItemId( 'Q22' ),
			// changes to Q33 are relevant because they affect enwiki
		];

		$this->subscriptions['dewiki'] = [
			new ItemId( 'Q22' ),
			// changes to Q22 are relevant because they affect dewiki
		];

		$this->changes = $this->getAllChanges();
	}

	/**
	 * @param int $changeId
	 * @param EntityId $entityId
	 * @param string $time
	 * @param Diff|null $siteLinkDiff
	 *
	 * @return Change
	 */
	private function newChange( $changeId, EntityId $entityId, $time, Diff $siteLinkDiff = null ) {
		$changeClass = $entityId->getEntityType() === Item::ENTITY_TYPE
			? ItemChange::class
			: EntityChange::class;

		$change = $this->getMockBuilder( $changeClass )
			->disableOriginalConstructor()
			->getMock();

		$change->expects( $this->never() )
			->method( 'getType' );

		$change->expects( $this->any() )
			->method( 'getTime' )
			->will( $this->returnValue( $time ) );

		$change->expects( $this->any() )
			->method( 'getAge' )
			->will( $this->returnValue( (int)wfTimestamp( TS_UNIX, $time ) - (int)wfTimestamp( TS_UNIX, $this->now ) ) );

		$change->expects( $this->any() )
			->method( 'getId' )
			->will( $this->returnValue( $changeId ) );

		$change->expects( $this->any() )
			->method( 'getObjectId' )
			->will( $this->returnValue( $entityId->getSerialization() ) );

		$change->expects( $this->any() )
			->method( 'getEntityId' )
			->will( $this->returnValue( $entityId ) );

		if ( $changeClass === ItemChange::class ) {
			$change->expects( $this->any() )
				->method( 'getSiteLinkDiff' )
				->will( $this->returnValue( $siteLinkDiff ) );
		}

		return $change;
	}

	public function testInitialValues() {
		$coordinator = $this->getMock( ChangeDispatchCoordinator::class );
		$dispatcher = new ChangeDispatcher(
			$coordinator,
			$this->getNotificationSender(),
			$this->getChunkedChangesAccess(),
			$this->getSubscriptionLookup()
		);

		$this->assertSame( $coordinator, $dispatcher->getDispatchCoordinator() );
		$this->assertFalse( $dispatcher->isVerbose() );
		$this->assertInstanceOf( MessageReporter::class, $dispatcher->getMessageReporter() );
		$this->assertInstanceOf( ExceptionHandler::class, $dispatcher->getExceptionHandler() );
		$this->assertSame( 1000, $dispatcher->getBatchSize() );
		$this->assertSame( 3, $dispatcher->getBatchChunkFactor() );
		$this->assertSame( 15, $dispatcher->getMaxChunks() );
	}

	public function testSetters() {
		$dispatcher = new ChangeDispatcher(
			$this->getMock( ChangeDispatchCoordinator::class ),
			$this->getNotificationSender(),
			$this->getChunkedChangesAccess(),
			$this->getSubscriptionLookup()
		);

		$dispatcher->setVerbose( true );
		$reporter = new NullMessageReporter();
		$dispatcher->setMessageReporter( $reporter );
		$exceptionHandler = $this->getMock( ExceptionHandler::class );
		$dispatcher->setExceptionHandler( $exceptionHandler );
		$dispatcher->setBatchSize( 1 );
		$dispatcher->setBatchChunkFactor( 1 );
		$dispatcher->setMaxChunks( 1 );

		$this->assertTrue( $dispatcher->isVerbose() );
		$this->assertSame( $reporter, $dispatcher->getMessageReporter() );
		$this->assertSame( $exceptionHandler, $dispatcher->getExceptionHandler() );
		$this->assertSame( 1, $dispatcher->getBatchSize() );
		$this->assertSame( 1, $dispatcher->getBatchChunkFactor() );
		$this->assertSame( 1, $dispatcher->getMaxChunks() );
	}

	public function testSelectClient() {
		$siteId = 'testwiki';

		$expectedClientState = [
			'chd_site' => $siteId,
			'chd_db' => $siteId,
			'chd_seen' => 0,
			'chd_touched' => '20140303000000',
			'chd_lock' => null
		];

		$coordinator = $this->getMock( ChangeDispatchCoordinator::class );

		$coordinator->expects( $this->once() )
			->method( 'selectClient' )
			->will( $this->returnValue( $expectedClientState ) );

		$coordinator->expects( $this->never() )
			->method( 'initState' );

		$dispatcher = $this->getChangeDispatcher( $coordinator );

		// This does nothing but call $coordinator->selectClient()
		$actualClientState = $dispatcher->selectClient();
		$this->assertEquals( $expectedClientState, $actualClientState );
	}

	public function provideGetPendingChanges() {
		$changes = $this->getAllChanges();

		return [
			'enwiki: one two three'
				=> [ 'enwiki', 0, 3, 1, [ $changes[1], $changes[2], $changes[3] ], 3 ],

			'enwiki: four five, chunkFactor=1'
				=> [ 'enwiki', 3, 2, 1, [ $changes[4], $changes[5] ], 5 ],

			'enwiki: five six, chunkFactor=2, scan to end'
				=> [ 'enwiki', 4, 3, 2, [ $changes[5], $changes[6] ], 8 ],

			'enwiki: five six, chunkFactor=1, scan to end'
				=> [ 'enwiki', 4, 3, 1, [ $changes[5], $changes[6] ], 8 ],

			'dewiki: three four seven, chunkFactor=1'
				=> [ 'dewiki', 2, 3, 1, [ $changes[3], $changes[4], $changes[7] ], 7 ],

			'dewiki: three four seven, chunkFactor=2'
				=> [ 'dewiki', 2, 3, 1, [ $changes[3], $changes[4], $changes[7] ], 7 ],

			'dewiki: seven eight'
				=> [ 'dewiki', 4, 3, 2, [ $changes[7], $changes[8] ], 8 ],
		];
	}

	/**
	 * @dataProvider provideGetPendingChanges
	 */
	public function testGetPendingChanges(
		$siteId,
		$afterId,
		$batchSize,
		$batchChunkFactor,
		array $expectedChanges,
		$expectedSeen
	) {
		$coordinator = $this->getMock( ChangeDispatchCoordinator::class );

		$dispatcher = $this->getChangeDispatcher( $coordinator );
		$dispatcher->setBatchSize( $batchSize );
		$dispatcher->setBatchChunkFactor( $batchChunkFactor );

		$pending = $dispatcher->getPendingChanges( $siteId, $afterId );

		$this->assertChanges( $expectedChanges, $pending[0] );
		$this->assertSame( $expectedSeen, $pending[1] );
	}

	public function testGetPendingChanges_maxChunks() {
		$dispatcher = new ChangeDispatcher(
			$this->getMock( ChangeDispatchCoordinator::class ),
			$this->getNotificationSender(),
			$this->getChunkedChangesAccess( $this->exactly( 1 ) ),
			$this->getSubscriptionLookup()
		);

		// 2 changes are loaded in each chunk
		$dispatcher->setBatchSize( 2 );
		$dispatcher->setBatchChunkFactor( 1 );

		// only process 1 chunk
		$dispatcher->setMaxChunks( 1 );

		$dispatcher->getPendingChanges( 'dewiki', 0 );
	}

	public function provideDispatchTo() {
		$changes = $this->getAllChanges();

		return [
			'enwiki: from the beginning' => [
				3,
				[
					'chd_site' => 'enwiki',
					'chd_db' => 'enwikidb',
					'chd_seen' => 0,
					'chd_touched' => '00000000000000',
					'chd_lock' => null
				],
				3,
				[
					[ 'enwiki', [ 1, 2, 3 ] ]
				]
			],
			'enwiki: scan to end' => [
				3,
				[
					'chd_site' => 'enwiki',
					'chd_db' => 'enwikidb',
					'chd_seen' => 4,
					'chd_touched' => $changes[4]->getTime(),
					'chd_lock' => null
				],
				8,
				[
					[ 'enwiki', [ 5, 6 ] ]
				]
			],
			'dewiki: from the beginning' => [
				3,
				[
					'chd_site' => 'dewiki',
					'chd_db' => 'dewikidb',
					'chd_seen' => 0,
					'chd_touched' => '00000000000000',
					'chd_lock' => null
				],
				7,
				[
					[ 'dewiki', [ 3, 4, 7 ] ]
				]
			],
			'dewiki: offset' => [
				2,
				[
					'chd_site' => 'dewiki',
					'chd_db' => 'dewikidb',
					'chd_seen' => 3,
					'chd_touched' => $changes[4]->getTime(),
					'chd_lock' => null
				],
				7,
				[
					[ 'dewiki', [ 4, 7 ] ]
				]
			],
		];
	}

	/**
	 * @dataProvider provideDispatchTo
	 */
	public function testDispatchTo( $batchSize, array $wikiState, $expectedFinalSeen, array $expectedNotifications ) {
		$expectedFinalState = array_merge( $wikiState, [ 'chd_seen' => $expectedFinalSeen ] );

		$coordinator = $this->getMock( ChangeDispatchCoordinator::class );

		$coordinator->expects( $this->never() )
			->method( 'lockClient' );

		$coordinator->expects( $this->once() )
			->method( 'releaseClient' )
			->with( $expectedFinalState );

		$notifications = [];
		$dispatcher = $this->getChangeDispatcher( $coordinator, $notifications );
		$dispatcher->setBatchSize( $batchSize );
		$dispatcher->dispatchTo( $wikiState );

		$this->assertNotifications( $expectedNotifications, $notifications );
	}

	/**
	 * @param Change[] $changes
	 *
	 * @return int[]
	 */
	private function getChangeIds( array $changes ) {
		return array_map( function( Change $change ) {
			return $change->getId();
		}, $changes );
	}

	/**
	 * @param Change[] $expected
	 * @param Change[] $actual
	 */
	private function assertChanges( array $expected, $actual ) {
		$expected = $this->getChangeIds( $expected );
		$actual = $this->getChangeIds( $actual );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @param array[] $expected
	 * @param array[] $notifications
	 */
	private function assertNotifications( array $expected, array $notifications ) {
		foreach ( $notifications as &$n ) {
			$n[1] = $this->getChangeIds( $n[1] );
		}

		$this->assertEquals( $expected, $notifications );
	}

}
