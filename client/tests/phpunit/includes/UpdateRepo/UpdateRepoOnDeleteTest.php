<?php

namespace Wikibase\Client\Tests\UpdateRepo;

use Title;
use User;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Client\UpdateRepo\UpdateRepoOnDelete;

/**
 * @covers Wikibase\Client\UpdateRepo\UpdateRepoOnDelete
 *
 * @group WikibaseClient
 * @group Test
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnDeleteTest extends \MediaWikiTestCase {

	/**
	 * Return some fake data for testing
	 *
	 * @return array
	 */
	protected function getFakeData() {
		$entityId = new ItemId( 'Q123' );

		$siteLinkLookupMock = $this->getMock( 'Wikibase\Lib\Store\SiteLinkLookup' );

		$siteLinkLookupMock->expects( $this->any() )
			->method( 'getEntityIdForSiteLink' )
			->will( $this->returnValue( $entityId ) );

		return array(
			'repoDB' => 'wikidata',
			'siteLinkLookup' => $siteLinkLookupMock,
			'user' => User::newFromName( 'RandomUserWhichDoesntExist' ),
			'siteId' => 'whatever',
			'title' => Title::newFromText( 'Delete me' ),
		);
	}

	/**
	 * @return UpdateRepoOnDelete
	 */
	protected function getNewUpdateRepoOnDelete() {
		static $updateRepo = null;

		if ( !$updateRepo ) {
			$data = $this->getFakeData();

			$updateRepo = new UpdateRepoOnDelete(
				$data['repoDB'],
				// Nobody knows why we need to clone over here, but it's not working
				// without... PHP is fun!
				clone $data['siteLinkLookup'],
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
		$updateRepo = $this->getNewUpdateRepoOnDelete();

		$this->assertFalse( $updateRepo->userIsValidOnRepo() );
	}

	/**
	 * Create a new job and verify the set params
	 */
	public function testCreateJob() {
		$updateRepo = $this->getNewUpdateRepoOnDelete();
		$job = $updateRepo->createJob();
		$itemId = new ItemId( 'Q123' );

		$data = $this->getFakeData();
		$this->assertInstanceOf( 'IJobSpecification', $job );
		$this->assertEquals( 'UpdateRepoOnDelete', $job->getType() );

		$params = $job->getParams();
		$this->assertEquals( $data['siteId'], $params['siteId'] );
		$this->assertEquals( $data['title'], $params['title'] );
		$this->assertEquals( $data['user'], $params['user'] );
		$this->assertEquals( $itemId->getSerialization(), $params['entityId'] );
	}

	public function testInjectJob() {
		$updateRepo = $this->getNewUpdateRepoOnDelete();
		$job = $updateRepo->createJob();

		$jobQueueGroupMock = $this->getJobQueueGroupMock( $job, true );

		$updateRepo->injectJob( $jobQueueGroupMock );
	}
}
