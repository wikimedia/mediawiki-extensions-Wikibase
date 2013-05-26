<?php
namespace Wikibase\Test;

use Wikibase\UpdateRepoOnMove;
use Wikibase\Client\WikibaseClient;
use Wikibase\Settings;

/**
 * Tests for the UpdateRepoOnMove class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group WikibaseClient
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
		static $ret = array();

		if ( !$ret ) {
			$ret = array(
				'repoDB' => wfWikiID(),
				'siteLinkLookup' => WikibaseClient::getDefaultInstance()->getStore()->getSiteLinkTable(),
				'user' => \User::newFromName( 'RandomUserWhichDoesntExist' ),
				'siteId' => Settings::get( 'siteGlobalID' ),
				'oldTitle' => \Title::newFromText( 'ThisOneDoesntExist' ),
				'newTitle' => \Title::newFromText( 'Bar' )
			);
		}

		return $ret;
	}

	/**
	 * Get a new object which thinks we're both the repo and client
	 *
	 * @return UpdateRepoOnMove
	 */
	protected function getNewLocal() {
		$moveData = $this->getFakeMoveData();

		$updateRepo = new UpdateRepoOnMove(
			$moveData['repoDB'],
			$moveData['siteLinkLookup'],
			$moveData['user'],
			$moveData['siteId'],
			$moveData['oldTitle'],
			$moveData['newTitle']
		);

		return $updateRepo;
	}

	/**
	 * Checks the type and params of a given job
	 *
	 * @param \Job $job
	 * @param array $moveData Expectations
	 */
	protected function assertIsValidJob( $job, $moveData ) {
		$this->assertInstanceOf( 'Job', $job );
		$this->assertEquals( 'UpdateRepoOnMove', $job->getType() );

		$params = $job->getParams();
		$this->assertEquals( $moveData['siteId'], $params['siteId'] );
		$this->assertEquals( $moveData['oldTitle'], $params['oldTitle'] );
		$this->assertEquals( $moveData['newTitle'], $params['newTitle'] );
		$this->assertEquals( $moveData['user'], $params['user'] );
		$this->assertTrue( array_key_exists( 'entityId', $params ) );
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

		$this->assertIsValidJob( $job, $this->getFakeMoveData() );
	}

	/**
	 * Inject a job (into a SimpleJobQueueGroupMock), verify the set params and remove it again
	 */
	public function testInjectJob() {
		$jobQueueGroup = new SimpleJobQueueGroupMock();
		$updateRepo = $this->getNewLocal();

		$updateRepo->injectJob( $jobQueueGroup );

		$job = $jobQueueGroup->pop( 'UpdateRepoOnMove' );
		$jobQueueGroup->ack( $job );

		$this->assertIsValidJob( $job, $this->getFakeMoveData() );
	}
}

/**
 * Extremely simple JobQueueGroup mock class
 */
class SimpleJobQueueGroupMock extends \JobQueueGroup {
	/**
	 * @var array
	 */
	protected $jobs = array();

	public function __construct() {}

	/**
	 * Push a job into the queue
	 *
	 * @param \Job $job
	 *
	 * @return bool
	 */
	public function push( $job ) {
		$this->jobs[] = $job;

		return true;
	}

	/**
	 * Get a job
	 *
	 * @return \Job
	 */
	public function pop() {
		return array_pop( $this->jobs );
	}

	public function ack() {}
}
