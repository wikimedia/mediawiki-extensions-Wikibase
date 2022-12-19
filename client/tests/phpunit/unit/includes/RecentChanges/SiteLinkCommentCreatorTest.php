<?php

namespace Wikibase\Client\Tests\Unit\RecentChanges;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;
use HashSiteStore;
use MediaWiki\MediaWikiServices;
use TestSites;
use Title;
use Wikibase\Client\RecentChanges\SiteLinkCommentCreator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Tests\Changes\TestChanges;

/**
 * @covers \Wikibase\Client\RecentChanges\SiteLinkCommentCreator
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkCommentCreatorTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider getEditCommentProvider
	 */
	public function testGetEditComment( Diff $siteLinkDiff, $action, $expected ) {
		$siteStore = new HashSiteStore( TestSites::getSites() );
		$lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'qqx' );
		$commentCreator = new SiteLinkCommentCreator( $lang, $siteStore, 'enwiki' );
		$comment = $commentCreator->getEditComment( $siteLinkDiff, $action, $this->getTitle( 'A fancy page' ) );

		$this->assertEquals( $expected, $comment );
	}

	public function getEditCommentProvider() {
		$changes = [];

		$updates = $this->getUpdates();

		foreach ( $updates as $update ) {
			$changes[] = [
				$update[0],
				EntityChange::UPDATE,
				$update[1],
			];
		}

		$changes[] = [
			$this->getDeleteDiff(),
			EntityChange::REMOVE,
			'(wikibase-comment-remove)',
		];

		$changes[] = [
			$this->getRestoreDiff(),
			EntityChange::RESTORE,
			'(wikibase-comment-restore)',
		];

		return $changes;
	}

	/**
	 * @dataProvider needsTargetSpecificSummaryProvider
	 */
	public function testNeedsTargetSpecificSummary( $expected, Diff $siteLinkDiff, Title $title ) {
		$siteStore = new HashSiteStore( TestSites::getSites() );
		$lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'qqx' );
		$commentCreator = new SiteLinkCommentCreator( $lang, $siteStore, 'enwiki' );
		$res = $commentCreator->needsTargetSpecificSummary( $siteLinkDiff, $title );

		$this->assertSame( $expected, $res );
	}

	public function needsTargetSpecificSummaryProvider() {
		$foo = $this->getTitle( 'Foo' );
		$bar = $this->getTitle( 'Bar' );
		$japan = $this->getTitle( 'Japan' );

		return [
			'Sitelink change that does affect the current page' => [
				true,
				$this->getChangeLinkDiff( 'Foo', 'Foo1' ),
				$foo,
			],
			'Sitelink change that does not affect current page' => [
				false,
				$this->getChangeLinkDiff( 'Foo', 'Foo1' ),
				$bar,
			],
			'Badge changes are not target specific' => [
				false,
				$this->getBadgeChangeDiff(),
				$japan,
			],
			'Remove link changes are not target specific' => [
				false,
				$this->getRemoveLinkDiff(),
				$japan,
			],
			'Add link changes are not target specific' => [
				false,
				$this->getAddLinkDiff(),
				$japan,
			],
		];
	}

	/**
	 * @param string $fullText
	 *
	 * @return Title
	 */
	private function getTitle( $fullText ) {
		$title = $this->createMock( Title::class );

		$title->method( 'getPrefixedText' )
			->willReturn( $fullText );

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
		$diff = new Diff( [
			'enwiki' => new DiffOpChange( 'Japan', 'Tokyo' ),
		] );

		return $diff;
	}

	protected function getBadgeChangeDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = $this->getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan', [ new ItemId( 'Q17' ) ] );

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
		$change = $changeFactory->newFromUpdate( EntityChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	private function getDeleteDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( EntityChange::REMOVE, $item, null );

		return $change->getSiteLinkDiff();
	}

	private function getRestoreDiff() {
		$item = $this->getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( EntityChange::RESTORE, null, $item );

		return $change->getSiteLinkDiff();
	}

	private function getUpdates() {
		$updates = [];

		$updates[] = [
			$this->getConnectDiff(),
			'(wikibase-comment-linked)',
		];

		$updates[] = [
			$this->getUnlinkDiff(),
			'(wikibase-comment-unlink)',
		];

		$updates[] = [
			$this->getLinkChangeDiff(),
			'(wikibase-comment-sitelink-change: [[:en:Japan]], [[:en:Tokyo]])',
		];

		$updates[] = [
			$this->getOldLinkChangeDiff(),
			'(wikibase-comment-sitelink-change: [[:en:Japan]], [[:en:Tokyo]])',
		];

		$updates[] = [
			$this->getBadgeChangeDiff(),
			null, // changes to badges do not get a special message
		];

		$updates[] = [
			$this->getAddLinkDiff(),
			'(wikibase-comment-sitelink-add: [[:de:Japan]])',
		];

		$updates[] = [
			$this->getAddSisterProjectLinkDiff(),
			'(wikibase-comment-sitelink-add: enwiktionary:Japan)',
		];

		$updates[] = [
			$this->getAddMultipleLinksDiff(),
			null, // currently multi-link diffs are not supported
		];

		$updates[] = [
			$this->getRemoveLinkDiff(),
			'(wikibase-comment-sitelink-remove: [[:de:Japan]])',
		];

		$updates[] = [
			$this->getChangeLinkDiff(),
			'(wikibase-comment-sitelink-change: [[:de:Japan]], [[:de:Tokyo]])',
		];

		$updates['Current page gets linked via link change'] = [
			$this->getChangeLinkDiff( 'Japan', 'A fancy page' ),
			'(wikibase-comment-linked)',
		];

		$updates['Current page gets unlinked via link change'] = [
			$this->getChangeLinkDiff( 'A fancy page', 'Japan' ),
			'(wikibase-comment-unlink)',
		];

		return $updates;
	}

}
