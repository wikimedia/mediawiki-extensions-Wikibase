<?php

namespace Wikibase\Client\Tests\RecentChanges;

use Wikibase\Client\RecentChanges\RevisionData;

/**
 * @covers Wikibase\Client\RecentChanges\RevisionData
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RevisionDataTest extends \PHPUnit_Framework_TestCase {

	public function testGetUserName() {
		$revisionData = $this->newRevisionData();
		$this->assertEquals( 'Cat', $revisionData->getUserName() );
	}

	public function testGetPageId() {
		$revisionData = $this->newRevisionData();
		$this->assertEquals( 5, $revisionData->getPageId() );
	}

	public function testGetRevId() {
		$revisionData = $this->newRevisionData();
		$this->assertEquals( 92, $revisionData->getRevId() );
	}

	public function testGetParentId() {
		$revisionData = $this->newRevisionData();
		$this->assertEquals( 90, $revisionData->getParentId() );
	}

	public function testGetTimestamp() {
		$revisionData = $this->newRevisionData();
		$this->assertEquals( '20130819111741', $revisionData->getTimestamp() );
	}

	public function testGetComment() {
		$revisionData = $this->newRevisionData();
		$this->assertEquals( array( 'key' => 'wikibase-comment-update' ), $revisionData->getComment() );
	}

	public function testGetSiteId() {
		$revisionData = $this->newRevisionData();
		$this->assertEquals( 'testrepo', $revisionData->getSiteId() );
	}

	private function newRevisionData() {
		$comment = array( 'key' => 'wikibase-comment-update' );
		return new RevisionData( 'Cat', 5, 92, 90, '20130819111741', $comment, 'testrepo' );
	}

}
