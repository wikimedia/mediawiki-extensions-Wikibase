<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use InvalidArgumentException;
use Wikibase\Repo\ChangeOp\ChangeOpSiteLink;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;

/**
 * @covers Wikibase\Repo\ChangeOp\ChangeOpSiteLink
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Michał Łazowik
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ChangeOpSiteLinkTest extends \PHPUnit\Framework\TestCase {

	public function invalidConstructorProvider() {
		$argLists = [];

		$argLists[] = [ 'enwiki', 1234 ];
		$argLists[] = [ 1234, 'Berlin' ];
		$argLists[] = [ 'plwiki', 'Warszawa', [ 'FA', 'GA' ] ];
		$argLists[] = [ 'plwiki', 'Warszawa', [ new ItemId( 'Q42' ), 'FA' ] ];
		$argLists[] = [ 'plwiki', 'Warszawa', [ new PropertyId( 'P42' ) ] ];

		return $argLists;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testConstructorWithInvalidArguments( $siteId, $linkPage, array $badges = null ) {
		new ChangeOpSiteLink( $siteId, $linkPage, $badges );
	}

	public function changeOpSiteLinkProvider() {
		$deSiteLink = new SiteLink( 'dewiki', 'Berlin' );
		$enSiteLink = new SiteLink( 'enwiki', 'Berlin', [ new ItemId( 'Q149' ) ] );
		$plSiteLink = new SiteLink( 'plwiki', 'Berlin', [ new ItemId( 'Q42' ) ] );

		$existingSiteLinks = [
			$deSiteLink,
			$plSiteLink
		];

		$args = [];

		// adding sitelink with badges
		$args[] = [
			$existingSiteLinks,
			new ChangeOpSiteLink( 'enwiki', 'Berlin', [ new ItemId( 'Q149' ) ] ),
			array_merge( $existingSiteLinks, [ $enSiteLink ] )
		];

		// deleting sitelink
		$args[] = [
			$existingSiteLinks,
			new ChangeOpSiteLink( 'dewiki', null ),
			[ $plSiteLink ]
		];

		// setting badges on existing sitelink
		$args[] = [
			$existingSiteLinks,
			new ChangeOpSiteLink( 'plwiki', 'Berlin', [ new ItemId( 'Q42' ), new ItemId( 'Q149' ) ] ),
			[
				$deSiteLink,
				new SiteLink( 'plwiki', 'Berlin', [ new ItemId( 'Q42' ), new ItemId( 'Q149' ) ] )
			]
		];

		// changing sitelink without modifying badges
		$args[] = [
			$existingSiteLinks,
			new ChangeOpSiteLink( 'plwiki', 'Test' ),
			[
				$deSiteLink,
				new SiteLink( 'plwiki', 'Test', [ new ItemId( 'Q42' ) ] )
			]
		];

		// change badges without modifying title
		$args[] = [
			$existingSiteLinks,
			new ChangeOpSiteLink( 'plwiki', null, [ new ItemId( 'Q149' ) ] ),
			[
				$deSiteLink,
				new SiteLink( 'plwiki', 'Berlin', [ new ItemId( 'Q149' ) ] )
			]
		];

		// add duplicate badges
		$args[] = [
			$existingSiteLinks,
			new ChangeOpSiteLink( 'plwiki', null, [ new ItemId( 'q42' ), new ItemId( 'Q149' ), new ItemId( 'Q42' ) ] ),
			[
				$deSiteLink,
				new SiteLink( 'plwiki', 'Berlin', [ new ItemId( 'Q42' ), new ItemId( 'Q149' ) ] )
			]
		];

		return $args;
	}

	/**
	 * @dataProvider changeOpSiteLinkProvider
	 */
	public function testApply( array $existingSiteLinks, ChangeOpSiteLink $changeOpSiteLink, array $expectedSiteLinks ) {
		$item = new Item();
		$item->setSiteLinkList( new SiteLinkList( $existingSiteLinks ) );

		$changeOpSiteLink->apply( $item );

		$this->assertEquals(
			$expectedSiteLinks,
			array_values( $item->getSiteLinkList()->toArray() )
		);
	}

	public function testGivenNoSitelinkOnSiteAndBadgeChangeRequested_validateReturnsError() {
		$item = new Item();
		$item->setSiteLinkList( new SiteLinkList( [ new SiteLink( 'plwiki', 'Berlin', [ new ItemId( 'Q42' ) ] ) ] ) );

		$changeOp = new ChangeOpSiteLink( 'enwiki', null, [ new ItemId( 'Q149' ) ] );

		$result = $changeOp->validate( $item );

		$this->assertFalse( $result->isValid() );
		$this->assertEquals( 'no-such-sitelink', $result->getErrors()[0]->getCode() );
	}

	public function summaryTestProvider() {
		$sitelinks = [
			new SiteLink( 'dewiki', 'Berlin' ),
			new SiteLink( 'ruwiki', 'Берлин', [ new ItemId( 'Q42' ) ] )
		];

		$cases = [];
		$badge = new ItemId( 'Q149' );

		// Add sitelink without badges
		$cases['add-sitelink-without-badges'] = [
			'add',
			[ 'Berlin' ],
			$sitelinks,
			new ChangeOpSiteLink( 'enwiki', 'Berlin', [] )
		];

		// Add sitelink with badges
		$cases['add-sitelink-with-badges'] = [
			'add-both',
			[ 'Berlin', [ $badge ] ],
			$sitelinks,
			new ChangeOpSiteLink( 'enwiki', 'Berlin', [ $badge ] )
		];

		// Set page name only for existing sitelink
		$cases['set-pagename-existing-sitelink'] = [
			'set',
			[ 'London' ],
			$sitelinks,
			new ChangeOpSiteLink( 'ruwiki', 'London' )
		];

		// Add badge to existing sitelink
		$cases['add-badges-to-existing-sitelink'] = [
			'set-badges',
			[ [ $badge ] ],
			$sitelinks,
			new ChangeOpSiteLink( 'dewiki', null, [ $badge ] )
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
			new ChangeOpSiteLink( 'ruwiki', null, [ $badge ] )
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
	) {
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

	public function testGetActions() {
		$changeOp = new ChangeOpSiteLink( 'enwiki', 'Berlin' );

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

}
