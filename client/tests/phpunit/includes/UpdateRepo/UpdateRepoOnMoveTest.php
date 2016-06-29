<?php

namespace Wikibase\Client\Tests\UpdateRepo;

use IJobSpecification;
use JobQueueGroup;
use JobQueueRedis;
use JobSpecification;
use PHPUnit_Framework_TestCase;
use Title;
use User;
use Wikibase\Client\UpdateRepo\UpdateRepoOnMove;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers Wikibase\Client\UpdateRepo\UpdateRepoOnMove
 * @covers Wikibase\Client\UpdateRepo\UpdateRepo
 *
 * @group WikibaseClient
 * @group Test
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnMoveTest extends PHPUnit_Framework_TestCase {

	/**
	 * Return some fake data for testing
	 *
	 * @return array
	 */
	private function getFakeMoveData() {
		$entityId = new ItemId( 'Q123' );

		$siteLinkLookupMock = $this->getMock( SiteLinkLookup::class );

		$siteLinkLookupMock->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->will( $this->returnValue( $entityId ) );

		return array(
			'repoDB' => 'wikidata',
			'siteLinkLookup' => $siteLinkLookupMock,
			'user' => User::newFromName( 'RandomUserWhichDoesntExist' ),
			'siteId' => 'whatever',
			'oldTitle' => Title::newFromText( 'ThisOneDoesntExist' ),
			'newTitle' => Title::newFromText( 'Bar' )
		);
	}

	/**
	 * Get a new object which thinks we're both the repo and client
	 *
	 * @return UpdateRepoOnMove
	 */
	private function getNewUpdateRepoOnMove() {
		static $updateRepo = null;

		if ( !$updateRepo ) {
			$moveData = $this->getFakeMoveData();

			$updateRepo = new UpdateRepoOnMove(
				$moveData['repoDB'],
				// Nobody knows why we need to clone over here, but it's not working
				// without... PHP is fun!
				clone $moveData['siteLinkLookup'],
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
		$jobQueueGroupMock = $this->getMockBuilder( JobQueueGroup::class )
			->disableOriginalConstructor()
			->getMock();

		$jobQueueGroupMock->expects( $this->once() )
			->method( 'push' )
			->will(
				$this->returnCallback( function( JobSpecification $job ) {
					$this->verifyJob( $job );
				} )
			);

		// Use JobQueueRedis over here, as mocking abstract classes sucks
		// and it doesn't matter anyway
		$jobQueue = $this->getMockBuilder( JobQueueRedis::class )
			->disableOriginalConstructor()
			->getMock();

		$jobQueue->expects( $this->any() )
			->method( 'delayedJobsEnabled' )
			->will( $this->returnValue( true ) );

		$jobQueueGroupMock->expects( $this->once() )
			->method( 'get' )
			->with( $this->equalTo( 'UpdateRepoOnMove' ) )
			->will( $this->returnValue( $jobQueue ) );

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
