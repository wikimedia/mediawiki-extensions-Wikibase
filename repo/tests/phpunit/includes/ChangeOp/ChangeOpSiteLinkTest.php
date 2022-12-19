<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\ChangeOp;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOpSiteLink;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpSiteLink
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Michał Łazowik
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ChangeOpSiteLinkTest extends TestCase {

	public function invalidConstructorProvider(): array {
		$argLists = [];

		$argLists[] = [ 'plwiki', 'Warszawa', [ 'FA', 'GA' ] ];
		$argLists[] = [ 'plwiki', 'Warszawa', [ new ItemId( 'Q42' ), 'FA' ] ];
		$argLists[] = [ 'plwiki', 'Warszawa', [ new NumericPropertyId( 'P42' ) ] ];

		return $argLists;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 */
	public function testConstructorWithInvalidArguments( $siteId, $linkPage, array $badges = null ): void {
		$this->expectException( InvalidArgumentException::class );
		new ChangeOpSiteLink( $siteId, $linkPage, $badges );
	}

	public function changeOpSiteLinkProvider(): array {
		$deSiteLink = new SiteLink( 'dewiki', 'Berlin' );
		$enSiteLink = new SiteLink( 'enwiki', 'Berlin', [ new ItemId( 'Q149' ) ] );
		$plSiteLink = new SiteLink( 'plwiki', 'Berlin', [ new ItemId( 'Q42' ) ] );

		$existingSiteLinks = [
			$deSiteLink,
			$plSiteLink,
		];

		$args = [];

		// adding sitelink with badges
		$args[] = [
			$existingSiteLinks,
			new ChangeOpSiteLink( 'enwiki', 'Berlin', [ new ItemId( 'Q149' ) ] ),
			array_merge( $existingSiteLinks, [ $enSiteLink ] ),
		];

		// setting badges on existing sitelink
		$args[] = [
			$existingSiteLinks,
			new ChangeOpSiteLink( 'plwiki', 'Berlin', [ new ItemId( 'Q42' ), new ItemId( 'Q149' ) ] ),
			[
				$deSiteLink,
				new SiteLink( 'plwiki', 'Berlin', [ new ItemId( 'Q42' ), new ItemId( 'Q149' ) ] ),
			],
		];

		// changing sitelink without modifying badges
		$args[] = [
			$existingSiteLinks,
			new ChangeOpSiteLink( 'plwiki', 'Test' ),
			[
				$deSiteLink,
				new SiteLink( 'plwiki', 'Test', [ new ItemId( 'Q42' ) ] ),
			],
		];

		// change badges without modifying title
		$args[] = [
			$existingSiteLinks,
			new ChangeOpSiteLink( 'plwiki', 'Berlin', [ new ItemId( 'Q149' ) ] ),
			[
				$deSiteLink,
				new SiteLink( 'plwiki', 'Berlin', [ new ItemId( 'Q149' ) ] ),
			],
		];

		// add duplicate badges
		$args[] = [
			$existingSiteLinks,
			new ChangeOpSiteLink( 'plwiki', 'Berlin', [ new ItemId( 'q42' ), new ItemId( 'Q149' ), new ItemId( 'Q42' ) ] ),
			[
				$deSiteLink,
				new SiteLink( 'plwiki', 'Berlin', [ new ItemId( 'Q42' ), new ItemId( 'Q149' ) ] ),
			],
		];

		return $args;
	}

	/**
	 * @dataProvider changeOpSiteLinkProvider
	 */
	public function testApply( array $existingSiteLinks, ChangeOpSiteLink $changeOpSiteLink, array $expectedSiteLinks ): void {
		$item = new Item();
		$item->setSiteLinkList( new SiteLinkList( $existingSiteLinks ) );

		$changeOpResult = $changeOpSiteLink->apply( $item );

		$this->assertEquals(
			$expectedSiteLinks,
			array_values( $item->getSiteLinkList()->toArray() )
		);
		$this->assertTrue( $changeOpResult->isEntityChanged() );
	}

	public function summaryTestProvider(): array {
		$sitelinks = [
			new SiteLink( 'dewiki', 'Berlin' ),
			new SiteLink( 'ruwiki', 'Берлин', [ new ItemId( 'Q42' ) ] ),
		];

		$cases = [];
		$badge = new ItemId( 'Q149' );

		// Add sitelink without badges
		$cases['add-sitelink-without-badges'] = [
			'add',
			[ 'Berlin' ],
			$sitelinks,
			new ChangeOpSiteLink( 'enwiki', 'Berlin', [] ),
		];

		// Add sitelink with badges
		$cases['add-sitelink-with-badges'] = [
			'add-both',
			[ 'Berlin', [ $badge ] ],
			$sitelinks,
			new ChangeOpSiteLink( 'enwiki', 'Berlin', [ $badge ] ),
		];

		// Set page name only for existing sitelink
		$cases['set-pagename-existing-sitelink'] = [
			'set',
			[ 'London' ],
			$sitelinks,
			new ChangeOpSiteLink( 'ruwiki', 'London' ),
		];

		// Add badge to existing sitelink
		$cases['add-badges-to-existing-sitelink'] = [
			'set-badges',
			[ [ $badge ] ],
			$sitelinks,
			new ChangeOpSiteLink( 'dewiki', 'Berlin', [ $badge ] ),
		];

		// Set page name and badges for existing sitelink
		$cases['set-pagename-badges-existing-sitelink'] = [
			'set-both',
			[ 'London', [ $badge ] ],
			$sitelinks,
			new ChangeOpSiteLink( 'dewiki', 'London', [ $badge ] ),
		];

		// Changes badges for existing sitelink
		$cases['change-badges-for-existing-sitelink'] = [
			'set-badges',
			[ [ $badge ] ],
			$sitelinks,
			new ChangeOpSiteLink( 'ruwiki', 'Берлин', [ $badge ] ),
		];

		return $cases;
	}

	/**
	 * @dataProvider summaryTestProvider
	 */
	public function testApplySummary(
		$expectedMessageKey,
		array $expectedArguments,
		array $sitelinks,
		ChangeOpSiteLink $changeOpSiteLink
	): void {
		$item = new Item();
		$item->setSiteLinkList( new SiteLinkList( $sitelinks ) );

		$summary = new Summary();
		$changeOpSiteLink->apply( $item, $summary );

		$this->assertSame( $expectedMessageKey, $summary->getMessageKey() );

		$this->assertEquals(
			$expectedArguments,
			$summary->getAutoSummaryArgs()
		);
	}

	public function testGetActions(): void {
		$changeOp = new ChangeOpSiteLink( 'enwiki', 'Berlin' );

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

}
