<?php

namespace Wikibase\Client\Tests\UpdateRepo;

use JobQueueGroup;
use JobSpecification;
use Title;
use User;
use Wikibase\Client\UpdateRepo\UpdateRepoOnDelete;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\UpdateRepo\UpdateRepoOnDelete
 * @covers Wikibase\Client\UpdateRepo\UpdateRepo
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
	private function getFakeData() {
		$entityId = new ItemId( 'Q123' );

		$siteLinkLookupMock = $this->getMock( 'Wikibase\Lib\Store\SiteLinkLookup' );

		$siteLinkLookupMock->expects( $this->any() )
			->method( 'getItemIdForSiteLink' )
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
	 * @param bool $cache Whether to cache the instance/ use a cached instance
	 * @param string $userValidationMethod
	 *
	 * @return UpdateRepoOnDelete
	 */
	private function getNewUpdateRepoOnDelete( $cache = true, $userValidationMethod = 'assumeSame' ) {
		static $updateRepoCached = null;

		if ( $updateRepoCached && $cache ) {
			return $updateRepoCached;
		}

		$data = $this->getFakeData();

		$updateRepo = new UpdateRepoOnDelete(
			$data['repoDB'],
			$data['siteLinkLookup'],
			$data['user'],
			$data['siteId'],
			$data['title'],
			$userValidationMethod
		);

		if ( $cache ) {
			$updateRepoCached = $updateRepo;
		}

		return $updateRepo;
	}

	/**
	 * Get a JobQueueGroup mock for the use in UpdateRepo::injectJob.
	 *
	 * @return JobQueueGroup
	 */
	private function getJobQueueGroupMock() {
		$jobQueueGroupMock = $this->getMockBuilder( 'JobQueueGroup' )
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
		$jobQueue = $this->getMockBuilder( 'JobQueueRedis' )
			->disableOriginalConstructor()
			->getMock();

		$jobQueueGroupMock->expects( $this->once() )
			->method( 'get' )
			->with( $this->equalTo( 'UpdateRepoOnDelete' ) )
			->will( $this->returnValue( $jobQueue ) );

		return $jobQueueGroupMock;
	}

	/**
	 * @dataProvider userIsValidOnRepoProvider
	 */
	public function testUserIsValidOnRepo( $expected, $userValidationMethod ) {
		$updateRepo = $this->getNewUpdateRepoOnDelete( false, $userValidationMethod );

		$this->assertSame( $expected, $updateRepo->userIsValidOnRepo() );
	}

	public function userIsValidOnRepoProvider() {
		return array(
			array( true, 'assumeSame' ),
			array( false, 'centralauth' )
		);
	}

	/**
	 * Verify a created job
	 *
	 * @param JobSpecification $job
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

		$updateRepo->injectJob( $this->getJobQueueGroupMock() );
	}

}
