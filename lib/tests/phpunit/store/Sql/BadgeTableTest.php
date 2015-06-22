<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\BadgeTable;

/**
 * @covers Wikibase\Lib\Store\BadgeTable
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group SiteLink
 * @group WikibaseStore
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class BadgeTableTest extends \MediaWikiTestCase {

	/**
	 * @var BadgeTable
	 */
	private $badgeTable;

	protected function setUp() {
		parent::setUp();

		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local badges table." );
		}

		$this->badgeTable = new BadgeTable( 'wb_badges_per_sitelink', false );
	}

	public function siteLinkProvider() {
		$siteLinks = array();

		$siteLinks[] = array( new SiteLink( 'enwiki', 'Foo', array( new ItemId( 'Q42' ) ) ) );
		$siteLinks[] = array( new SiteLink( 'dewiki', 'Bar', array( new ItemId( 'Q42' ),  new ItemId( 'Q43' ) ) ) );
		$siteLinks[] = array( new SiteLink( 'frwiki', 'Baz' ) );

		return $siteLinks;
	}

	public function badgesProvider() {
		$badges = array();
		$enwiki = new SiteLink( 'enwiki', 'Foo' );
		$dewiki = new SiteLink( 'dewiki', 'Bar' );

		$badges[] = array( new ItemId( 'Q42' ), array( 'dewiki' => $dewiki, 'enwiki' => $enwiki ) );
		$badges[] = array( new ItemId( 'Q43' ), array( 'dewiki' => $dewiki ) );

		return $badges;
	}

	/**
	 * @dataProvider siteLinkProvider
	 */
	public function testSaveBadgesOfSiteLink( SiteLink $siteLink ) {
		$res = $this->badgeTable->saveBadgesOfSiteLink( $siteLink );
		$this->assertTrue( $res );
	}

	public function testUpdateBadgesOfSiteLink() {
		// save initial badges
		$siteLink = new SiteLink( 'enwiki', 'Test', array( new ItemId( 'Q1' ) ) );
		$this->badgeTable->saveBadgesOfSiteLink( $siteLink );

		// modify badges, and save again
		$siteLink = new SiteLink( 'enwiki', 'Test', array( new ItemId( 'Q2' ), new ItemId( 'Q3' ) ) );
		$this->badgeTable->saveBadgesOfSiteLink( $siteLink );

		// check that the update worked correctly
		$actualBadges = $this->badgeTable->getBadgesForSiteLink( $siteLink );
		$expectedBadges = $siteLink->getBadges();

		$missingBadges = array_diff( $expectedBadges, $actualBadges );
		$extraBadges = array_diff( $actualBadges, $expectedBadges );

		$this->assertEmpty( $missingBadges, 'Missing badges' );
		$this->assertEmpty( $extraBadges, 'Extra badges' );
	}

	/**
	 * @depends testSaveBadgesOfSiteLink
	 * @dataProvider siteLinkProvider
	 */
	public function testGetBadgesOfSiteLink( SiteLink $siteLink ) {
		$badges = $this->badgeTable->getBadgesForSiteLink( $siteLink );

		$this->assertEquals(
			$siteLink->getBadges(),
			$badges
		);
	}

	/**
	 * @depends testSaveBadgesOfSiteLink
	 * @dataProvider badgesProvider
	 */
	public function testGetSiteLinksForBadge( ItemId $badge, array $expected ) {
		$siteLinks = $this->badgeTable->getSiteLinksForBadge( $badge );
		$this->assertArrayEquals( $expected, $siteLinks );
	}

	/**
	 * @depends testSaveBadgesOfSiteLink
	 * @dataProvider badgesProvider
	 */
	public function testGetSiteLinksForBadge_oneSite( ItemId $badge, array $expected ) {
		foreach ( array( 'enwiki', 'dewiki', 'frwiki' ) as $siteId ) {
			$siteLinks = $this->badgeTable->getSiteLinksForBadge( $badge, $siteId );

			if ( isset( $expected[$siteId] ) ) {
				$this->assertEquals( array( $expected[$siteId] ), $siteLinks );
			} else {
				$this->assertEmpty( $siteLinks );
			}
		}
	}

	/**
	 * @depends testSaveBadgesOfSiteLink
	 * @dataProvider siteLinkProvider
	 */
	public function testDeleteBadgesOfSiteLink( SiteLink $siteLink ) {
		$this->assertTrue(
			$this->badgeTable->deleteBadgesOfSiteLink( $siteLink ) !== false
		);

		$this->assertEmpty(
			$this->badgeTable->getBadgesForSiteLink( $siteLink )
		);
	}

	/**
	 * @depends testSaveBadgesOfSiteLink
	 * @dataProvider siteLinkProvider
	 */
	public function testClear( SiteLink $siteLink ) {
		$this->assertTrue(
			$this->badgeTable->clear() !== false
		);

		$this->assertEmpty(
			$this->badgeTable->getBadgesForSiteLink( $siteLink )
		);
	}

}
