<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\UpdateRepoOnMoveJob;

/**
 * @covers Wikibase\UpdateRepoOnMoveJob
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
			'entityId' => new ItemId( 'q123' ),
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
		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( 'Wikibase\Summary is only available on repo' );
		}
		$moveData = $this->getSampleData();
		$job = $this->getNewFromMove( $moveData );

		$summary = $job->getSummary( 'SiteID', 'Test', 'MoarTest' );

		$this->assertEquals(
			'/* clientsitelink-update:0|SiteID|SiteID:Test|SiteID:MoarTest */',
			$summary->toString()
		);
	}

}
