<?php

namespace Wikibase\Client\Tests\Unit\UpdateRepo;

use IJobSpecification;
use JobQueueGroup;
use JobQueueRedis;
use JobSpecification;
use Psr\Log\NullLogger;
use Title;
use User;
use Wikibase\Client\UpdateRepo\UpdateRepoOnMove;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Rdbms\ReplicationWaiter;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers \Wikibase\Client\UpdateRepo\UpdateRepoOnMove
 * @covers \Wikibase\Client\UpdateRepo\UpdateRepo
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnMoveTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Return some fake data for testing
	 *
	 * @return array
	 */
	private function getFakeMoveData() {
		$entityId = new ItemId( 'Q123' );

		$siteLinkLookupMock = $this->createMock( SiteLinkLookup::class );

		$siteLinkLookupMock->method( 'getItemIdForLink' )
			->willReturn( $entityId );

		return [
			'repoDB' => 'wikidata',
			'siteLinkLookup' => $siteLinkLookupMock,
			'user' => User::newFromName( 'RandomUserWhichDoesntExist' ),
			'siteId' => 'whatever',
			'oldTitle' => Title::makeTitle( NS_MAIN, 'ThisOneDoesntExist' ),
			'newTitle' => Title::makeTitle( NS_MAIN, 'Bar' ),
		];
	}

	/**
	 * Get a new object which thinks we're both the repo and client
	 *
	 * @return UpdateRepoOnMove
	 */
	private function getNewUpdateRepoOnMove() {
		static $updateRepo = null;

		if ( !$updateRepo ) {
			$replicationWaiter = $this->createMock( ReplicationWaiter::class );
			$replicationWaiter->method( 'getMaxLag' )
				->willReturn( [ '', -1, 0 ] );
			$clientDb = $this->createMock( ClientDomainDb::class );
			$clientDb->method( 'replication' )
				->willReturn( $replicationWaiter );
			$moveData = $this->getFakeMoveData();

			$updateRepo = new UpdateRepoOnMove(
				// Nobody knows why we need to clone over here, but it's not working
				// without... PHP is fun!
				clone $moveData['siteLinkLookup'],
				new NullLogger(),
				$clientDb,
				$moveData['user'],
				$moveData['siteId'],
				$moveData['oldTitle'],
				$moveData['newTitle']
			);
		}

		return $updateRepo;
	}

	/**
	 * Get a JobQueueGroup mock for the use in UpdateRepo::injectJob.
	 *
	 * @return JobQueueGroup
	 */
	private function getJobQueueGroupMock() {
		$jobQueueGroupMock = $this->createMock( JobQueueGroup::class );

		$jobQueueGroupMock->expects( $this->once() )
			->method( 'push' )
			->willReturnCallback( function( JobSpecification $job ) {
				$this->verifyJob( $job );
			} );

		// Use JobQueueRedis over here, as mocking abstract classes sucks
		// and it doesn't matter anyway
		$jobQueue = $this->getMockBuilder( JobQueueRedis::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'supportsDelayedJobs' ] )
			->getMock();

		$jobQueue->method( 'supportsDelayedJobs' )
			->willReturn( true );

		$jobQueueGroupMock->expects( $this->once() )
			->method( 'get' )
			->with( 'UpdateRepoOnMove' )
			->willReturn( $jobQueue );

		return $jobQueueGroupMock;
	}

	/**
	 * Verify a created job
	 *
	 * @param JobSpecification $job
	 */
	public function verifyJob( JobSpecification $job ) {
		$itemId = new ItemId( 'Q123' );

		$moveData = $this->getFakeMoveData();
		$this->assertInstanceOf( IJobSpecification::class, $job );
		$this->assertEquals( 'UpdateRepoOnMove', $job->getType() );

		$params = $job->getParams();
		$this->assertEquals( $moveData['siteId'], $params['siteId'] );
		$this->assertEquals( $moveData['oldTitle'], $params['oldTitle'] );
		$this->assertEquals( $moveData['newTitle'], $params['newTitle'] );
		$this->assertEquals( $moveData['user'], $params['user'] );
		$this->assertEquals( $itemId->getSerialization(), $params['entityId'] );
	}

	public function testInjectJob() {
		$updateRepo = $this->getNewUpdateRepoOnMove();

		$jobQueueGroupMock = $this->getJobQueueGroupMock();

		$updateRepo->injectJob( $jobQueueGroupMock );
	}

	public function testIsApplicable() {
		$updateRepo = $this->getNewUpdateRepoOnMove();

		$this->assertTrue( $updateRepo->isApplicable() );
	}

}
