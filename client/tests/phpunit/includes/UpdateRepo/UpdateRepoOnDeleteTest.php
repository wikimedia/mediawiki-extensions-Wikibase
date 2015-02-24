<?php

namespace Wikibase\Client\Tests\UpdateRepo;

use Title;
use User;
use JobSpecification;
use Wikibase\Client\UpdateRepo\UpdateRepoOnDelete;
use Wikibase\DataModel\Entity\ItemId;

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
class UpdateRepoOnDeleteTest extends \PHPUnit_Framework_TestCase {

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
	private function getNewUpdateRepoOnDelete() {
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
	 * @return object
	 */
	protected function getJobQueueGroupMock() {
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

		$jobQueueGroupMock->expects( $this->once() )
			->method( 'get' )
			->with( $this->equalTo( 'UpdateRepoOnDelete' ) )
			->will( $this->returnValue( $jobQueue ) );

		return $jobQueueGroupMock;
	}

	public function testUserIsValidOnRepo() {
		$updateRepo = $this->getNewUpdateRepoOnDelete();

		$this->assertFalse( $updateRepo->userIsValidOnRepo() );
	}

	/**
	 * Verify a created job
	 *
	 * @param Job $job
	 */
	public function verifyJob( JobSpecification $job ) {
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

		$jobQueueGroupMock = $this->getJobQueueGroupMock( true );

		$updateRepo->injectJob( $jobQueueGroupMock );
	}
}
