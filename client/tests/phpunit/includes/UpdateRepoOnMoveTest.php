<?php
namespace Wikibase\Test;

use Wikibase\UpdateRepoOnMove;
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
 * @group Database
 * @group WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnMoveTest extends \MediaWikiTestCase {
	/**
	 * @return UpdateRepoOnMove
	 */
	protected function getNewFromMove() {
		$updateRepo = UpdateRepoOnMove::newFromMove(
			\Title::newFromText( 'Foo' ),
			\Title::newFromText( 'Bar' ),
			\User::newFromName( 'RandomUserWhichDoesntExist' )
		);

		return $updateRepo;
	}

	/**
	 * Get a new object which thinks we're both the repo and client
	 *
	 * @return UpdateRepoOnMove
	 */
	protected function getNewLocal() {
		$updateRepo = new UpdateRepoOnMove(
			wfWikiID(),
			\User::newFromName( 'RandomUserWhichDoesntExist' ),
			Settings::get( 'siteGlobalID' ),
			\Title::newFromText( 'Foo' ),
			\Title::newFromText( 'Bar' )
		);

		return $updateRepo;
	}

	/**
	 * Checks the type and params of a given job
	 *
	 * @param \Job $job
	 */
	protected function jobAsserts( $job ) {
		$this->assertInstanceOf( 'Job', $job );
		$this->assertEquals( 'UpdateRepoOnMove', $job->getType() );

		$params = $job->getParams();
		$this->assertEquals( Settings::get( 'siteGlobalID' ), $params['siteID'] );
		$this->assertEquals( 'Foo', $params['oldTitle'] );
		$this->assertEquals( 'Bar', $params['newTitle'] );
		$this->assertEquals( 'RandomUserWhichDoesntExist', $params['user'] );
	}

	public function testNewFromMove() {
		$updateRepo = $this->getNewFromMove();

		$this->assertInstanceOf( 'Wikibase\UpdateRepoOnMove', $updateRepo );
	}

	public function testVerifyRepoUser() {
		$updateRepo = $this->getNewLocal();

		$this->assertFalse( $updateRepo->verifyRepoUser() );
	}

	/**
	 * Create a new job and verify the set params
	 */
	public function testGetJob() {
		$updateRepo = $this->getNewLocal();
		$job = $updateRepo->getJob();

		$this->jobAsserts( $job );
	}

	/**
	 * Inject a job (into the local DB), verify the set params and remove it again
	 */
	public function testInjectJob() {
		$updateRepo = $this->getNewLocal();

		$this->assertTrue( $updateRepo->injectJob() );

		$jobQueueGroup = \JobQueueGroup::singleton();

		// Let's just hope this is the job we've just injected, but
		// that's very likely as this job shouldn't occur in the client queue
		$job = $jobQueueGroup->pop( 'UpdateRepoOnMove' );
		$jobQueueGroup->ack( $job );

		$this->jobAsserts( $job );
	}
}
