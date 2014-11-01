<?php

namespace Wikibase\Client\Tests\UpdateRepo;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Client\UpdateRepo\UpdateRepoOnMove;

/**
 * @covers Wikibase\UpdateRepoOnMove
 *
 * @group WikibaseClient
 * @group Test
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnMoveTest extends \MediaWikiTestCase {

	/**
	 * Return some fake data for testing
	 *
	 * @return array
	 */
	protected function getFakeMoveData() {
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
	protected function getNewLocal() {
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
	 * @param \Job $expectedJob The job that is expected to be pushed
	 * @param bool $success Whether the push will succeed
	 *
	 * @return object
	 */
	protected function getJobQueueGroupMock( $expectedJob, $success ) {
		$jobQueueGroupMock = $this->getMockBuilder( '\JobQueueGroup' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueueGroupMock->expects( $this->once() )
			->method( 'push' )
			->will( $this->returnValue( $success ) )
			->with( $this->equalTo( $expectedJob ) );

		return $jobQueueGroupMock;
	}

	public function testUserIsValidOnRepo() {
		$updateRepo = $this->getNewLocal();

		$this->assertFalse( $updateRepo->userIsValidOnRepo() );
	}

	/**
	 * Create a new job and verify the set params
	 */
	public function testCreateJob() {
		$updateRepo = $this->getNewLocal();
		$job = $updateRepo->createJob();
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
		$updateRepo = $this->getNewLocal();
		$job = $updateRepo->createJob();

		$jobQueueGroupMock = $this->getJobQueueGroupMock( $job, true );

		$updateRepo->injectJob( $jobQueueGroupMock );
	}
}
