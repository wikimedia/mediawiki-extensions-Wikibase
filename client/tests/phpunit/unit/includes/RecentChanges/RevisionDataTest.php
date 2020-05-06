<?php

namespace Wikibase\Client\Tests\Unit\RecentChanges;

use Wikibase\Client\RecentChanges\RevisionData;

/**
 * @covers \Wikibase\Client\RecentChanges\RevisionData
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RevisionDataTest extends \PHPUnit\Framework\TestCase {

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
		$this->assertSame( '20130819111741', $revisionData->getTimestamp() );
	}

	public function testGetComment() {
		$revisionData = $this->newRevisionData();
		$this->assertEquals( 'Kitten Comment', $revisionData->getComment() );
	}

	public function testGetCommentHtml() {
		$revisionData = $this->newRevisionData();
		$this->assertEquals( '<span>Kitten Comment</span>', $revisionData->getCommentHtml() );
	}

	public function testGetSiteId() {
		$revisionData = $this->newRevisionData();
		$this->assertEquals( 'testrepo', $revisionData->getSiteId() );
	}

	public function testGetChangeParams() {
		$revisionData = $this->newRevisionData();
		$expected = [
			'page_id' => 5,
			'rev_id' => 92,
			'parent_id' => 90,
		];
		$this->assertEquals( $expected, $revisionData->getChangeParams() );
	}

	private function newRevisionData() {
		$comment = 'Kitten Comment';
		$commentHtml = '<span>' . htmlspecialchars( $comment ) . '</span>';
		$changeParams = [
			'page_id' => 5,
			'rev_id' => 92,
			'parent_id' => 90,
		];

		return new RevisionData(
			'Cat', '20130819111741', $comment, $commentHtml, 'testrepo', 0, $changeParams
		);
	}

}
