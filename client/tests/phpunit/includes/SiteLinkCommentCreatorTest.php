<?php

namespace Wikibase\Client\Tests;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;
use Language;
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
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkCommentCreatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getEditCommentProvider
	 */
	public function testGetEditComment( Diff $siteLinkDiff, $action, $expected ) {
		$commentCreator = new SiteLinkCommentCreator( Language::factory( 'qqx' ), 'enwiki' );
		$comment = $commentCreator->getEditComment( $siteLinkDiff, $action );

		$this->assertEquals( $expected, $comment );
	}

	public function getEditCommentProvider() {
		$changes = array();

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

	protected function getNewItem() {
		return new Item( new ItemId( 'Q1' ) );
	}

	protected function getConnectDiff() {
		$item = $this->getNewItem();

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	protected function getUnlinkDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->removeLinkWithSiteId( 'enwiki' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	protected function getLinkChangeDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Tokyo' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
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

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	protected function getAddLinkDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );
		$item2->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Japan' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	protected function getAddMultipleLinksDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );
		$item2->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Japan' );
		$item2->getSiteLinkList()->addNewSiteLink( 'frwiki', 'Japan' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	protected function getRemoveLinkDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	protected function getChangeLinkDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );
		$item2->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Tokyo' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	protected function getDeleteDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( ItemChange::REMOVE, $item, null );

		return $change->getSiteLinkDiff();
	}

	protected function getRestoreDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( ItemChange::RESTORE, null, $item );

		return $change->getSiteLinkDiff();
	}

	protected function getUpdates() {
		$updates = array();

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
			'(wikibase-comment-sitelink-change: [[:enwiki:Japan]], [[:enwiki:Tokyo]])',
		);

		$updates[] = array(
			$this->getOldLinkChangeDiff(),
			'(wikibase-comment-sitelink-change: [[:enwiki:Japan]], [[:enwiki:Tokyo]])',
		);

		$updates[] = array(
			$this->getBadgeChangeDiff(),
			null, // changes to badges do not get a special message
		);

		$updates[] = array(
			$this->getAddLinkDiff(),
			'(wikibase-comment-sitelink-add: [[:dewiki:Japan]])',
		);

		$updates[] = array(
			$this->getAddMultipleLinksDiff(),
			null, // currently multi-link diffs are not supported
		);

		$updates[] = array(
			$this->getRemoveLinkDiff(),
			'(wikibase-comment-sitelink-remove: [[:dewiki:Japan]])',
		);

		$updates[] = array(
			$this->getChangeLinkDiff(),
			'(wikibase-comment-sitelink-change: [[:dewiki:Japan]], [[:dewiki:Tokyo]])',
		);

		return $updates;
	}

}
