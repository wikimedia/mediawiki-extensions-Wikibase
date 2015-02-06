<?php

namespace Wikibase\Client\Tests\UpdateRepo;

use JobQueueGroup;
use JobSpecification;
use Wikibase\Client\UpdateRepo\UpdateRepoOnMove;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\UpdateRepo\UpdateRepoOnMove
 *
 * @group WikibaseClient
 * @group Test
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnMoveTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Return some fake data for testing
	 *
	 * @return array
	 */
	private function getFakeMoveData() {
		$entityId = new ItemId( 'Q123' );

		$siteLinkLookupMock = $this->getMock( 'Wikibase\Lib\Store\SiteLinkLookup' );

		$siteLinkLookupMock->expects( $this->any() )
			->method( 'getEntityIdForSiteLink' )
			->will( $this->returnValue( $entityId ) );

		return array(
			'repoDB' => 'wikidata',
			'siteLinkLookup' => $siteLinkLookupMock,
			'user' => \User::newFromName( 'RandomUserWhichDoesntExist' ),
			'siteId' => 'whatever',
			'oldTitle' => \Title::newFromText( 'ThisOneDoesntExist' ),
			'newTitle' => \Title::newFromText( 'Bar' )
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
		$jobQueueGroupMock = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->getMock();

		$self = $this; // PHP 5.3 compat
		$jobQueueGroupMock->expects( $this->once() )
			->method( 'push' )
			->will(
				$this->returnCallback( function( JobSpecification $job ) use( $self ) {
					$self->verifyJob( $job );
				} )
			);

		// Use JobQueueRedis over here, as mocking abstract classes sucks
		// and it doesn't matter anyway
		$jobQueue = $this->getMockBuilder( '\JobQueueRedis' )
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

	public function testUserIsValidOnRepo() {
		$updateRepo = $this->getNewUpdateRepoOnMove();

		$this->assertFalse( $updateRepo->userIsValidOnRepo() );
	}

	/**
	 * Verify a created job
	 *
	 * @param JobSpecification $job
	 */
	private function verifyJob( JobSpecification $job ) {
		$itemId = new ItemId( 'Q123' );

		$moveData = $this->getFakeMoveData();
		$this->assertInstanceOf( 'IJobSpecification', $job );
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

		$jobQueueGroupMock = $this->getJobQueueGroupMock( true );

		$updateRepo->injectJob( $jobQueueGroupMock );
	}

}
