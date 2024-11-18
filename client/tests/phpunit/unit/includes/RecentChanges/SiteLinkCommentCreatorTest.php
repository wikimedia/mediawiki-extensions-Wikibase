<?php

namespace Wikibase\Client\Tests\Unit\RecentChanges;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;
use MediaWiki\MediaWikiServices;
use MediaWiki\Site\HashSiteStore;
use MediaWiki\Tests\Site\TestSites;
use MediaWiki\Title\Title;
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

	public static function getEditCommentProvider() {
		$changes = [];

		$updates = self::getUpdates();

		foreach ( $updates as $update ) {
			$changes[] = [
				$update[0],
				EntityChange::UPDATE,
				$update[1],
			];
		}

		$changes[] = [
			self::getDeleteDiff(),
			EntityChange::REMOVE,
			'(wikibase-comment-remove)',
		];

		$changes[] = [
			self::getRestoreDiff(),
			EntityChange::RESTORE,
			'(wikibase-comment-restore)',
		];

		return $changes;
	}

	/**
	 * @dataProvider needsTargetSpecificSummaryProvider
	 */
	public function testNeedsTargetSpecificSummary( $expected, Diff $siteLinkDiff, string $titleText ) {
		$title = $this->getTitle( $titleText );
		$siteStore = new HashSiteStore( TestSites::getSites() );
		$lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'qqx' );
		$commentCreator = new SiteLinkCommentCreator( $lang, $siteStore, 'enwiki' );
		$res = $commentCreator->needsTargetSpecificSummary( $siteLinkDiff, $title );

		$this->assertSame( $expected, $res );
	}

	public static function needsTargetSpecificSummaryProvider() {
		$foo = 'Foo';
		$bar = 'Bar';
		$japan = 'Japan';

		return [
			'Sitelink change that does affect the current page' => [
				true,
				self::getChangeLinkDiff( 'Foo', 'Foo1' ),
				$foo,
			],
			'Sitelink change that does not affect current page' => [
				false,
				self::getChangeLinkDiff( 'Foo', 'Foo1' ),
				$bar,
			],
			'Badge changes are not target specific' => [
				false,
				self::getBadgeChangeDiff(),
				$japan,
			],
			'Remove link changes are not target specific' => [
				false,
				self::getRemoveLinkDiff(),
				$japan,
			],
			'Add link changes are not target specific' => [
				false,
				self::getAddLinkDiff(),
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

	protected static function getNewItem() {
		return new Item( new ItemId( 'Q1' ) );
	}

	protected static function getConnectDiff() {
		$item = self::getNewItem();

		$item2 = self::getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		return self::getSiteLinkDiffForUpdate( $item, $item2 );
	}

	protected static function getUnlinkDiff() {
		$item = self::getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = self::getNewItem();
		$item2->getSiteLinkList()->removeLinkWithSiteId( 'enwiki' );

		return self::getSiteLinkDiffForUpdate( $item, $item2 );
	}

	protected static function getLinkChangeDiff() {
		$item = self::getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = self::getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Tokyo' );

		return self::getSiteLinkDiffForUpdate( $item, $item2 );
	}

	protected static function getOldLinkChangeDiff() {
		$diff = new Diff( [
			'enwiki' => new DiffOpChange( 'Japan', 'Tokyo' ),
		] );

		return $diff;
	}

	protected static function getBadgeChangeDiff() {
		$item = self::getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = self::getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan', [ new ItemId( 'Q17' ) ] );

		return self::getSiteLinkDiffForUpdate( $item, $item2 );
	}

	protected static function getAddLinkDiff() {
		$item = self::getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = self::getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );
		$item2->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Japan' );

		return self::getSiteLinkDiffForUpdate( $item, $item2 );
	}

	private static function getAddSisterProjectLinkDiff() {
		$item = self::getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = self::getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiktionary', 'Japan' );

		return self::getSiteLinkDiffForUpdate( $item, $item2 );
	}

	private static function getAddMultipleLinksDiff() {
		$item = self::getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$item2 = self::getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );
		$item2->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Japan' );
		$item2->getSiteLinkList()->addNewSiteLink( 'frwiki', 'Japan' );

		return self::getSiteLinkDiffForUpdate( $item, $item2 );
	}

	private static function getRemoveLinkDiff() {
		$item = self::getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Japan' );

		$item2 = self::getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		return self::getSiteLinkDiffForUpdate( $item, $item2 );
	}

	private static function getChangeLinkDiff( $oldName = 'Japan', $newName = 'Japan' ) {
		$item = self::getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', $oldName );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Japan' );

		$item2 = self::getNewItem();
		$item2->getSiteLinkList()->addNewSiteLink( 'enwiki', $newName );
		$item2->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Tokyo' );

		return self::getSiteLinkDiffForUpdate( $item, $item2 );
	}

	private static function getSiteLinkDiffForUpdate( Item $item, Item $item2 ) {
		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( EntityChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	private static function getDeleteDiff() {
		$item = self::getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( EntityChange::REMOVE, $item, null );

		return $change->getSiteLinkDiff();
	}

	private static function getRestoreDiff() {
		$item = self::getNewItem();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Japan' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newFromUpdate( EntityChange::RESTORE, null, $item );

		return $change->getSiteLinkDiff();
	}

	private static function getUpdates() {
		$updates = [];

		$updates[] = [
			self::getConnectDiff(),
			'(wikibase-comment-linked)',
		];

		$updates[] = [
			self::getUnlinkDiff(),
			'(wikibase-comment-unlink)',
		];

		$updates[] = [
			self::getLinkChangeDiff(),
			'(wikibase-comment-sitelink-change: [[:en:Japan]], [[:en:Tokyo]])',
		];

		$updates[] = [
			self::getOldLinkChangeDiff(),
			'(wikibase-comment-sitelink-change: [[:en:Japan]], [[:en:Tokyo]])',
		];

		$updates[] = [
			self::getBadgeChangeDiff(),
			null, // changes to badges do not get a special message
		];

		$updates[] = [
			self::getAddLinkDiff(),
			'(wikibase-comment-sitelink-add: [[:de:Japan]])',
		];

		$updates[] = [
			self::getAddSisterProjectLinkDiff(),
			'(wikibase-comment-sitelink-add: enwiktionary:Japan)',
		];

		$updates[] = [
			self::getAddMultipleLinksDiff(),
			null, // currently multi-link diffs are not supported
		];

		$updates[] = [
			self::getRemoveLinkDiff(),
			'(wikibase-comment-sitelink-remove: [[:de:Japan]])',
		];

		$updates[] = [
			self::getChangeLinkDiff(),
			'(wikibase-comment-sitelink-change: [[:de:Japan]], [[:de:Tokyo]])',
		];

		$updates['Current page gets linked via link change'] = [
			self::getChangeLinkDiff( 'Japan', 'A fancy page' ),
			'(wikibase-comment-linked)',
		];

		$updates['Current page gets unlinked via link change'] = [
			self::getChangeLinkDiff( 'A fancy page', 'Japan' ),
			'(wikibase-comment-unlink)',
		];

		return $updates;
	}

}
