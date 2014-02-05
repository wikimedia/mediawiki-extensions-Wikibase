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
		$item->setDescription( 'en', 'en:Test badge' );
		$item->setDescription( 'de', 'de:Test badge' );

		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->with( $this->logicalOr( new ItemId( 'Q4880' ), new ItemId( 'Q123' ) ) )
			->will( $this->returnCallback( function( $id ) use ( $item ) {
				if ( $id == new ItemId( 'Q4880' ) ) {
					return $item;
				}
				return null;
			} ) );

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
			array( array( 'Q43', 'Q148' ), Title::newFromText( 'Georg Friedrich Haendel' ), Title::makeTitle( 0, 'George Frideric Handel', '', 'en' ), 'passing enwiki title' ),
			array( array(), Title::newFromText( 'Georg Friedrich Haendel' ), Title::makeTitle( 0, 'Georg Friedrich Haendel', '', 'nl' ), 'passing nlwiki title' ),
			array( array( 'Q43', 'Q148' ), Title::newFromText( 'User:Georg Friedrich Haendel' ), Title::makeTitle( 0, 'George Frideric Handel', '', 'en' ), 'passing enwiki for non-main namespace title' ),
			array( array(), Title::newFromText( 'Johann Sebastian Bach' ), Title::makeTitle( 0, 'Johann Sebastian Bach', '', 'en' ), 'passing an unknown title' ),
			array( array(), Title::newFromText( 'Georg Friedrich Haendel' ), Title::makeTitle( 0, 'Georg Friedrich Haendel', '', 'it' ), 'passing a site without link' ),
		);
	}

	/**
	 * @dataProvider getTitleProvider
	 */
	public function testGetTitle( $expected, $badge, $lang, $message ) {
		$languageLinkBadgeHandler = $this->getLanguageLinkBadgeHandler();

		$this->assertEquals(
			$expected,
			$languageLinkBadgeHandler->getTitle( $badge, $lang ),
			$message
		);
	}

	public function getTitleProvider() {
		return array(
			array( 'en:Test badge', 'Q4880', 'en', 'title for en' ),
			array( 'de:Test badge', 'Q4880', 'de', 'title for de' ),
			array( null, 'Q4880', 'fr', 'title for fr' ),
			array( null, 'Q123', 'en', 'non existing badge' ),
		);
	}

}
