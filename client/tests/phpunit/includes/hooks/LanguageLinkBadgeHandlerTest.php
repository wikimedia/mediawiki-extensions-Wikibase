<?php

namespace Wikibase\Test;

use Wikibase\Client\Hooks\LanguageLinkBadgeHandler;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Item;
use Title;

/**
 * @covers Wikibase\Client\Hooks\LanguageLinkBadgeHandler
 *
 * @since 0.5
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LanguageLinkBadgeHandlerTest extends \MediaWikiTestCase {

	private function getLanguageLinkBadgeHandler() {
		$siteLinkLookup = $this->getMockBuilder( 'Wikibase\SiteLinkTable' )
			->disableOriginalConstructor()
			->getMock();

		$siteLinkLookup->expects( $this->any() )
			->method( 'getEntityIdForSiteLink' )
			->with( $this->logicalOr(
				new SiteLink( 'dewiki', 'Georg Friedrich Haendel' ),
				new SiteLink( 'dewiki', 'User:Georg Friedrich Haendel' ),
				new SiteLink( 'dewiki', 'Johann Sebastian Bach' )
			) )
			->will( $this->returnCallback( function( $link ) {
				if ( $link->getPageName() == 'Georg Friedrich Haendel'
					|| $link->getPageName() == 'User:Georg Friedrich Haendel'
				) {
					return new ItemId( 'Q4880' );
				}
				return null;
			} ) );

		$entityLookup = $this->getMockBuilder( 'Wikibase\CachingEntityLoader' )
			->disableOriginalConstructor()
			->getMock();

		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'enwiki', 'George Frideric Handel', array( new ItemId( 'Q43' ), new ItemId( 'Q148' ) ) ) );
		$item->addSiteLink( new SiteLink( 'nlwiki', 'Georg Friedrich Haendel' ) );

		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->with( new ItemId( 'Q4880' ) )
			->will( $this->returnValue( $item ) );

		$sites = MockSiteStore::newFromTestSites();

		return new LanguageLinkBadgeHandler(
			'dewiki',
			$siteLinkLookup,
			$entityLookup,
			$sites
		);
	}

	/**
	 * @dataProvider getSiteLinkProvider
	 */
	public function testGetSiteLink( $expected, Title $title, $siteId, $message ) {
		$languageLinkBadgeHandler = $this->getLanguageLinkBadgeHandler();

		$this->assertEquals(
			$expected,
			$languageLinkBadgeHandler->getSiteLink( $title, $siteId ),
			$message
		);
	}

	public function getSiteLinkProvider() {
		$siteLinkEnwiki = new SiteLink( 'enwiki', 'George Frideric Handel', array( new ItemId( 'Q43' ), new ItemId( 'Q148' ) ) );
		$siteLinkNlwiki = new SiteLink( 'nlwiki', 'Georg Friedrich Haendel' );
		return array(
			array( $siteLinkEnwiki, Title::newFromText( 'Georg Friedrich Haendel' ), 'enwiki', 'passing enwiki' ),
			array( $siteLinkNlwiki, Title::newFromText( 'Georg Friedrich Haendel' ), 'nlwiki', 'passing nlwiki' ),
			array( $siteLinkEnwiki, Title::newFromText( 'User:Georg Friedrich Haendel' ), 'enwiki', 'passing enwiki for non-main namespace title' ),
			array( null, Title::newFromText( 'Johann Sebastian Bach' ), 'enwiki', 'passing an unknown title' ),
			array( null, Title::newFromText( 'Georg Friedrich Haendel' ), 'itwiki', 'passing a site without link' ),
		);
	}

	/**
	 * @dataProvider getBadgesProvider
	 */
	public function testGetBadges( $expected, Title $title, Title $languageLinkTitle, $message ) {
		$languageLinkBadgeHandler = $this->getLanguageLinkBadgeHandler();

		$this->assertEquals(
			$expected,
			$languageLinkBadgeHandler->getBadges( $title, $languageLinkTitle ),
			$message
		);
	}

	public function getBadgesProvider() {
		return array(
			array( array( 'Q43', 'Q148' ), Title::newFromText( 'Georg Friedrich Haendel' ), Title::newFromText( 'en:George Frideric Handel' ), 'passing enwiki title' ),
			array( array(), Title::newFromText( 'Georg Friedrich Haendel' ), Title::newFromText( 'nl:Georg Friedrich Haendel' ), 'passing nlwiki title' ),
			array( array( 'Q43', 'Q148' ), Title::newFromText( 'User:Georg Friedrich Haendel' ), Title::newFromText( 'en:George Frideric Handel' ), 'passing enwiki for non-main namespace title' ),
			array( array(), Title::newFromText( 'Johann Sebastian Bach' ), Title::newFromText( 'en:Johann Sebastian Bach' ), 'passing an unknown title' ),
			array( array(), Title::newFromText( 'Georg Friedrich Haendel' ), Title::newFromText( 'it:Georg Friedrich Haendel' ), 'passing a site without link' ),
		);
	}

}
