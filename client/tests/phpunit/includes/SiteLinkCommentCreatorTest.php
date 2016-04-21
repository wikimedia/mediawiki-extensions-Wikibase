<?php

namespace Wikibase\Client\Tests;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;
use HashSiteStore;
use Language;
use TestSites;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\ItemChange;
use Wikibase\SiteLinkCommentCreator;
use Wikibase\Test\TestChanges;

/**
 * @covers Wikibase\SiteLinkCommentCreator
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group SiteLinkCommentCreatorTest
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkCommentCreatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getEditCommentProvider
	 */
	public function testGetEditComment( Diff $siteLinkDiff, $action, $expected ) {
		$siteStore = new HashSiteStore( TestSites::getSites() );
		$commentCreator = new SiteLinkCommentCreator( Language::factory( 'qqx' ), $siteStore, 'enwiki' );
		$comment = $commentCreator->getEditComment( $siteLinkDiff, $action, $this->getTitle( 'A fancy page' ) );

		$this->assertEquals( $expected, $comment );
	}

	public function getEditCommentProvider() {
		$changes = [];

		$updates = $this->getUpdates();

		foreach ( $updates as $update ) {
			$changes[] = array(
				$update[0],
				ItemChange::UPDATE,
				$update[1]
			);
		}

		$changes[] = array(
			$this->getDeleteDiff(),
			ItemChange::REMOVE,
			'(wikibase-comment-remove)'
		);

		$changes[] = array(
			$this->getRestoreDiff(),
			ItemChange::RESTORE,
			'(wikibase-comment-restore)'
		);

		return $changes;
	}

	/**
	 * @dataProvider needsTargetSpecificSummaryProvider
	 */
	public function testNeedsTargetSpecificSummary( $expected, Diff $siteLinkDiff, Title $title ) {
		$siteStore = new HashSiteStore( TestSites::getSites() );
		$commentCreator = new SiteLinkCommentCreator( Language::factory( 'qqx' ), $siteStore, 'enwiki' );
		$res = $commentCreator->needsTargetSpecificSummary( $siteLinkDiff, $title );

		$this->assertSame( $expected, $res );
	}

	public function needsTargetSpecificSummaryProvider() {
		$foo = $this->getTitle( 'Foo' );
		$bar = $this->getTitle( 'Bar' );
		$japan = $this->getTitle( 'Japan' );

		return array(
			'Sitelink change that does affect the current page' => array(
				true,
				$this->getChangeLinkDiff( 'Foo', 'Foo1' ),
				$foo
			),
			'Sitelink change that does not affect current page' => array(
				false,
				$this->getChangeLinkDiff( 'Foo', 'Foo1' ),
				$bar
			),
			'Badge changes are not target specific' => array(
				false,
				$this->getBadgeChangeDiff(),
				$japan
			),
			'Remove link changes are not target specific' => array(
				false,
				$this->getRemoveLinkDiff(),
				$japan
			),
			'Add link changes are not target specific' => array(
				false,
				$this->getAddLinkDiff(),
				$japan
			)
		);
	}

	/**
	 * @param string $fullText
	 *
	 * @return Title
	 */
	private function getTitle( $fullText ) {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( $fullText ) );

		return $title;
	}

	protected function getNewItem() {
		return new Item( new ItemId( 'Q1' ) );
	}

	protected function getConnectDiff() {
		$item = $this->getNewItem();

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		return $this->getSiteLinkDiffForUpdate( $item, $item2 );
	}

	protected function getUnlinkDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->removeLinkWithSiteId( 'enwiki' );

		return $this->getSiteLinkDiffForUpdate( $item, $item2 );
	}

	protected function getLinkChangeDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Tokyo' );

		return $this->getSiteLinkDiffForUpdate( $item, $item2 );
	}

	protected function getOldLinkChangeDiff() {
		$diff = new Diff( array(
			'enwiki' => new DiffOpChange( 'Japan', 'Tokyo' )
		) );

		return $diff;
	}

	protected function getBadgeChangeDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan', array( new ItemId( 'Q17' ) ) );

		return $this->getSiteLinkDiffForUpdate( $item, $item2 );
	}

	protected function getAddLinkDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );
		$item2->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Japan' );

		return $this->getSiteLinkDiffForUpdate( $item, $item2 );
	}

	private function getAddSisterProjectLinkDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiktionary', 'Japan' );

		return $this->getSiteLinkDiffForUpdate( $item, $item2 );
	}

	private function getAddMultipleLinksDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );
		$item2->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Japan' );
		$item2->getSiteLinkList()->addNewSiteLink( 'frwiki', 'Japan' );

		return $this->getSiteLinkDiffForUpdate( $item, $item2 );
	}

	private function getRemoveLinkDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		return $this->getSiteLinkDiffForUpdate( $item, $item2 );
	}

	private function getChangeLinkDiff( $oldName = 'Japan', $newName = 'Japan' ) {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', $oldName );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', $newName );
		$item2->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Tokyo' );

		return $this->getSiteLinkDiffForUpdate( $item, $item2 );
	}

	private function getSiteLinkDiffForUpdate( Item $item, Item $item2 ) {
		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	private function getDeleteDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( ItemChange::REMOVE, $item, null );

		return $change->getSiteLinkDiff();
	}

	private function getRestoreDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( ItemChange::RESTORE, null, $item );

		return $change->getSiteLinkDiff();
	}

	private function getUpdates() {
		$updates = [];

		$updates[] = array(
			$this->getConnectDiff(),
			'(wikibase-comment-linked)',
		);

		$updates[] = array(
			$this->getUnlinkDiff(),
			'(wikibase-comment-unlink)',
		);

		$updates[] = array(
			$this->getLinkChangeDiff(),
			'(wikibase-comment-sitelink-change: [[:en:Japan]], [[:en:Tokyo]])',
		);

		$updates[] = array(
			$this->getOldLinkChangeDiff(),
			'(wikibase-comment-sitelink-change: [[:en:Japan]], [[:en:Tokyo]])',
		);

		$updates[] = array(
			$this->getBadgeChangeDiff(),
			null, // changes to badges do not get a special message
		);

		$updates[] = array(
			$this->getAddLinkDiff(),
			'(wikibase-comment-sitelink-add: [[:de:Japan]])',
		);

		$updates[] = array(
			$this->getAddSisterProjectLinkDiff(),
			'(wikibase-comment-sitelink-add: enwiktionary:Japan)',
		);

		$updates[] = array(
			$this->getAddMultipleLinksDiff(),
			null, // currently multi-link diffs are not supported
		);

		$updates[] = array(
			$this->getRemoveLinkDiff(),
			'(wikibase-comment-sitelink-remove: [[:de:Japan]])',
		);

		$updates[] = array(
			$this->getChangeLinkDiff(),
			'(wikibase-comment-sitelink-change: [[:de:Japan]], [[:de:Tokyo]])',
		);

		$updates['Current page gets linked via link change'] = array(
			$this->getChangeLinkDiff( 'Japan', 'A fancy page' ),
			'(wikibase-comment-linked)',
		);

		$updates['Current page gets unlinked via link change'] = array(
			$this->getChangeLinkDiff( 'A fancy page', 'Japan' ),
			'(wikibase-comment-unlink)',
		);

		return $updates;
	}

}
