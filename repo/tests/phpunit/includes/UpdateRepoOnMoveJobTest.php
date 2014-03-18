<?php

namespace Wikibase\Test;

use Title;
use Wikibase\UpdateRepoOnMoveJob;

/**
 * @covers Wikibase\UpdateRepoOnMoveJob
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoOnMoveJobTest extends \MediaWikiTestCase {

	public function testGetSummary() {
		$job = new UpdateRepoOnMoveJob( Title::newMainPage() );

		$summary = $job->getSummary( 'SiteID', 'Test', 'MoarTest' );

		$this->assertEquals( 'clientsitelink-update', $summary->getMessageKey() );
		$this->assertEquals( 'SiteID', $summary->getLanguageCode() );
		$this->assertEquals(
			array( 'SiteID:Test', 'SiteID:MoarTest' ),
			$summary->getCommentArgs()
		);
	}

}
