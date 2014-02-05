<?php

namespace Wikibase\Test;

use Wikibase\Client\Hooks\LanguageLinkBadgeHandler;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\ItemId;
use Wikibase\DataModel\Item;
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

	/**
	 * @var SiteLink
	 */
	private static $siteLinkEnwiki;

	/**
	 * @var SiteLink
	 */
	private static $siteLinkNlwiki;

	private function getLanguageLinkBadgeHandler() {
		$siteLinkLookup = $this->getMock(
			'Wikibase\SiteLinkTable',
			array( 'getEntityIdForSiteLink' ),
			array( 'SiteLinkTable', true )
		);

		$siteLinkLookup->expects( $this->any() )
			->method( 'getEntityIdForSiteLink' )
			->with( new SiteLink( 'dewiki', 'Georg Friedrich Händel' ) )
			->will( $this->returnValue( new ItemId( 'Q4880' ) ) );

		$entityLookup = $this->getMock(
			'Wikibase\CachingEntityLoader',
			array( 'getEntity' ),
			array( 'CachingEntityLoader', true )
		);

		$item = Item::newEmpty();
		$item->addSiteLink( self::$siteLinkEnwiki );
		$item->addSiteLink( self::$siteLinkNlwiki );

		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->with( new ItemId( 'Q4880' ) )
			->will( $this->returnValue( $item ) );

		$languageLinkBadgeHandler = new LanguageLinkBadgeHandler(
			'dewiki',
			self::$siteLinkLookup,
			self::$entityLookup
		);
	}

	private function initSiteLinks() {
		self::$siteLinkEnwiki = new SiteLink( 'enwiki', 'George Frideric Handel', array( new ItemId( 'Q43' ), new ItemId( 'Q148' ) ) );
		self::$siteLinkNlwiki = new SiteLink( 'nlwiki', 'Georg Friedrich Händel' );
	}

	/**
	 * @dataProvider getSiteLinkProvider
	 */
	public function testGetSiteLink( $expected, Title $title, $siteId, $message ) {
		$languageLinkBageHandler = $this->getLanguageLinkBadgeHandler();

		$this->assertEquals(
			$expected,
			$languageLinkBadgeHandler->getSiteLink( $title, $siteId ),
			$message
		);
	}

	public function getSiteLinkProvider() {
		$this->initSiteLinks();
		return array(
			array( self::$siteLinkEnwiki, Title::newFromText( 'de:Georg Friedrich Händel' ), 'enwiki', 'passing enwiki' ),
			array( self::$siteLinkNlwiki, Title::newFromText( 'de:Georg Friedrich Händel' ), 'nlwiki', 'passing nlwiki' ),
			array( null, Title::newFromText( 'de:Johann Sebastian Bach' ), 'enwiki', 'passing an unknown title' ),
			array( null, Title::newFromText( 'de:Georg Friedrich Händel' ), 'nlwiki', 'passing a site without link' ),
		);
	}

	/**
	 * @dataProvider getBadgesProvider
	 */
	public function testGetBadges( $exptected, Title $title, Title $languageLinkTitle, $message ) {
		$languageLinkBageHandler = $this->getLanguageLinkBadgeHandler();

		$this->assertEquals(
			$expected,
			$languageLinkBadgeHandler->getBadges( $title, $languageLinkTitle ),
			$message
		);
	}

	public function getBadgesProvider() {
		$this->initSiteLinks();
		return array(
			array( array( 'Q43', 'Q148' ), Title::newFromText( 'de:Georg Friedrich Händel' ), Title::newFromText( 'en:George Frideric Handel' ), 'passing enwiki title' ),
			array( array(), Title::newFromText( 'de:Georg Friedrich Händel' ), Title::newFromText( 'nl:Georg Friedrich Händel' ), 'passing nlwiki title' ),
			array( array(), Title::newFromText( 'de:Johann Sebastian Bach' ), 'enwiki', 'passing an unknown title' ),
			array( array(), Title::newFromText( 'de:Georg Friedrich Händel' ), 'nlwiki', 'passing a site without link' ),
		);
	}

}
