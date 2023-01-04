<?php

namespace Wikibase\Client\Tests\Unit\UpdateRepo;

use IJobSpecification;
use JobQueueGroup;
use JobQueueRedis;
use JobSpecification;
use Psr\Log\NullLogger;
use Title;
use User;
use Wikibase\Client\UpdateRepo\UpdateRepoOnDelete;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Rdbms\ReplicationWaiter;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers \Wikibase\Client\UpdateRepo\UpdateRepoOnDelete
 * @covers \Wikibase\Client\UpdateRepo\UpdateRepo
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnDeleteTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Return some fake data for testing
	 *
	 * @return array
	 */
	private function getFakeData() {
		$entityId = new ItemId( 'Q123' );

		$siteLinkLookupMock = $this->createMock( SiteLinkLookup::class );

		$siteLinkLookupMock->method( 'getItemIdForLink' )
			->willReturn( $entityId );

		return [
			'repoDB' => 'wikidata',
			'siteLinkLookup' => $siteLinkLookupMock,
			'user' => User::newFromName( 'RandomUserWhichDoesntExist' ),
			'siteId' => 'whatever',
			'title' => Title::makeTitle( NS_MAIN, 'Delete me' ),
		];
	}

	/**
	 * @return UpdateRepoOnDelete
	 */
	private function getNewUpdateRepoOnDelete() {
		static $updateRepo = null;

		if ( !$updateRepo ) {
			$data = $this->getFakeData();
			$replicationWaiter = $this->createMock( ReplicationWaiter::class );
			$replicationWaiter->method( 'getMaxLag' )
				->willReturn( [ '', -1, 0 ] );
			$clientDb = $this->createMock( ClientDomainDb::class );
			$clientDb->method( 'replication' )
				->willReturn( $replicationWaiter );

			$updateRepo = new UpdateRepoOnDelete(
				// Nobody knows why we need to clone over here, but it's not working
				// without... PHP is fun!
				clone $data['siteLinkLookup'],
				new NullLogger(),
				$clientDb,
				$data['user'],
				$data['siteId'],
				$data['title']
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
		$jobQueue = $this->createMock( JobQueueRedis::class );

		$jobQueueGroupMock->expects( $this->once() )
			->method( 'get' )
			->with( 'UpdateRepoOnDelete' )
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

		$data = $this->getFakeData();
		$this->assertInstanceOf( IJobSpecification::class, $job );
		$this->assertEquals( 'UpdateRepoOnDelete', $job->getType() );

		$params = $job->getParams();
		$this->assertEquals( $data['siteId'], $params['siteId'] );
		$this->assertEquals( $data['title'], $params['title'] );
		$this->assertEquals( $data['user'], $params['user'] );
		$this->assertEquals( $itemId->getSerialization(), $params['entityId'] );
	}

	public function testInjectJob() {
		$updateRepo = $this->getNewUpdateRepoOnDelete();

		$updateRepo->injectJob( $this->getJobQueueGroupMock() );
	}

	public function testIsApplicable() {
		$updateRepo = $this->getNewUpdateRepoOnDelete();

		$this->assertTrue( $updateRepo->isApplicable() );
	}

}
