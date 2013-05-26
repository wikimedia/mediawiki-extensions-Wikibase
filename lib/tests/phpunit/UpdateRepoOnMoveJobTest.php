<?php
namespace Wikibase\Test;

use Wikibase\UpdateRepoOnMoveJob;
use Wikibase\EntityId;
use Wikibase\Settings;

/**
 * Tests for the UpdateRepoOnMoveJob
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
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnMoveJobTest extends \MediaWikiTestCase {
	/**
	 * @param array $moveData
	 *
	 * @return UpdateRepoOnMoveJob
	 */
	public function getNewFromMove( $moveData ) {
		return UpdateRepoOnMoveJob::newFromMove(
			$moveData['oldTitle'],
			$moveData['newTitle'],
			$moveData['entityId'],
			$moveData['user'],
			$moveData['siteId']
		);
	}

	/**
	 * @return array
	 */
	public function getSampleData() {
		return array(
			'oldTitle' => \Title::newFromText( 'Foo' ),
			'newTitle' => \Title::newFromText( 'Bar' ),
			'entityId' => new EntityId( 'Item', 123 ),
			'user' => \User::newFromName( 'RandomUserWhichDoesntExist' ),
			'siteId' => wfWikiID() // Doesn't really matter what we use here
		);
	}

	/**
	 * Checks the type and params of a job created from a move
	 */
	public function testNewFromMove() {
		$moveData = $this->getSampleData();
		$job = $this->getNewFromMove( $moveData );

		$this->assertInstanceOf( 'Job', $job );
		$this->assertEquals( 'UpdateRepoOnMove', $job->getType() );

		$params = $job->getParams();
		$this->assertEquals( $moveData['siteId'], $params['siteId'] );
		$this->assertEquals( $moveData['entityId'], $params['entityId'] );
		$this->assertEquals( $moveData['oldTitle'], $params['oldTitle'] );
		$this->assertEquals( $moveData['newTitle'], $params['newTitle'] );
		$this->assertEquals( $moveData['user'], $params['user'] );
	}

	/**
	 * @group WikibaseRepoTest
	 */
	public function testGetSummary() {
		$moveData = $this->getSampleData();
		$job = $this->getNewFromMove( $moveData );

		$summary = $job->getSummary( 'SiteID', 'Test', 'MoarTest' );

		$this->assertEquals(
			'/* clientsitelink-update:0|SiteID|SiteID:Test|SiteID:MoarTest */',
			$summary->toString()
		);

	}
}
