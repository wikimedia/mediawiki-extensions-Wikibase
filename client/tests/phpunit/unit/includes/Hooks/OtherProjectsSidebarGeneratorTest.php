<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use HashSiteStore;
use MediaWiki\MediaWikiServices;
use MediaWikiSite;
use SiteLookup;
use TestSites;
use Title;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\Client\Hooks\SiteLinksForDisplayLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Term\Term;

/**
 * @covers \Wikibase\Client\Hooks\OtherProjectsSidebarGenerator
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Marius Hoch < hoo@online.de >
 */
class OtherProjectsSidebarGeneratorTest extends \PHPUnit\Framework\TestCase {

	private const BADGE_ITEM_ID = 'Q4242';
	private const BADGE_ITEM_LABEL = 'Badge Label';
	private const BADGE_CSS_CLASS = 'badge-class';

	/**
	 * @dataProvider projectLinkSidebarProvider
	 */
	public function testBuildProjectLinkSidebar(
		array $siteIdsToOutput,
		array $result,
		SidebarLinkBadgeDisplay $sidebarLinkBadgeDisplay
	) {
		$targetTitle = Title::makeTitle( NS_MAIN, 'Nyan Cat' );
		$otherProjectSidebarGenerator = new OtherProjectsSidebarGenerator(
			'enwiki',
			$this->getSiteLinkForDisplayLookup( 'getSiteLinksForPageTitle', $targetTitle ),
			$this->getSiteLookup(),
			$sidebarLinkBadgeDisplay,
			$siteIdsToOutput
		);

		$this->assertEquals(
			$result,
			$otherProjectSidebarGenerator->buildProjectLinkSidebar( $targetTitle )
		);
	}

	/**
	 * @dataProvider projectLinkSidebarProvider
	 */
	public function testBuildProjectLinksSidebarFromItemId(
		array $siteIdsToOutput,
		array $result,
		SidebarLinkBadgeDisplay $sidebarLinkBadgeDisplay
	) {
		$itemId = new ItemId( 'Q1' );
		$otherProjectSidebarGenerator = new OtherProjectsSidebarGenerator(
			'enwiki',
			$this->getSiteLinkForDisplayLookup( 'getSiteLinksForItemId', $itemId ),
			$this->getSiteLookup(),
			$sidebarLinkBadgeDisplay,
			$siteIdsToOutput
		);

		$this->assertEquals(
			$result,
			$otherProjectSidebarGenerator->buildProjectLinkSidebarFromItemId( $itemId )
		);
	}

	public function projectLinkSidebarProvider() {
		$wiktionaryLink = [
			'msg' => 'wikibase-otherprojects-wiktionary',
			'class' => 'wb-otherproject-link wb-otherproject-wiktionary',
			'href' => 'https://en.wiktionary.org/wiki/Nyan_Cat',
			'hreflang' => 'en',
		];
		$wikiquoteLink = [
			'msg' => 'wikibase-otherprojects-wikiquote',
			'class' => 'wb-otherproject-link wb-otherproject-wikiquote',
			'href' => 'https://en.wikiquote.org/wiki/Nyan_Cat',
			'hreflang' => 'en',
		];
		$wikipediaLink = [
			'msg' => 'wikibase-otherprojects-wikipedia',
			'class' => 'wb-otherproject-link wb-otherproject-wikipedia ' .
				'badge-' . self::BADGE_ITEM_ID . ' ' . self::BADGE_CSS_CLASS,
			'href' => 'https://en.wikipedia.org/wiki/Nyan_Cat',
			'hreflang' => 'en',
			'itemtitle' => self::BADGE_ITEM_LABEL,
		];

		return [
			[
				[],
				[],
				$this->getSidebarLinkBadgeDisplay(),
			],
			[
				[ 'spam', 'spam2' ],
				[],
				$this->getSidebarLinkBadgeDisplay(),
			],
			[
				[ 'enwiktionary' ],
				[ $wiktionaryLink ],
				$this->getSidebarLinkBadgeDisplay(),
			],
			[
				[ 'enwiki' ],
				[ $wikipediaLink ],
				$this->getSidebarLinkBadgeDisplay(),
			],
			[
				// Make sure results are sorted alphabetically by their group names
				[ 'enwiktionary', 'enwiki', 'enwikiquote' ],
				[ $wikipediaLink, $wikiquoteLink, $wiktionaryLink ],
				$this->getSidebarLinkBadgeDisplay(),
			],
		];
	}

	/**
	 * @return SiteLookup
	 */
	private function getSiteLookup() {
		$siteStore = new HashSiteStore( TestSites::getSites() );

		$site = new MediaWikiSite();
		$site->setGlobalId( 'enwikiquote' );
		$site->setGroup( 'wikiquote' );
		$site->setLanguageCode( 'en' );
		$site->setPath( MediaWikiSite::PATH_PAGE, 'https://en.wikiquote.org/wiki/$1' );
		$siteStore->saveSite( $site );

		return $siteStore;
	}

	/**
	 * @return SiteLinksForDisplayLookup
	 */
	private function getSiteLinkForDisplayLookup( string $expectedMethod, $expectedArgument ) {
		$lookup = $this->createMock( SiteLinksForDisplayLookup::class );
		$lookup->method( $expectedMethod )
			->with( $expectedArgument )
			->willReturn( [
				'enwikiquote' => new SiteLink( 'enwikiquote', 'Nyan Cat' ),
				'enwiki' => new SiteLink( 'enwiki', 'Nyan Cat', [ new ItemId( self::BADGE_ITEM_ID ) ] ),
				'enwiktionary' => new SiteLink( 'enwiktionary', 'Nyan Cat' ),
			] );
		return $lookup;
	}

	/**
	 * @return SidebarLinkBadgeDisplay
	 */
	private function getSidebarLinkBadgeDisplay() {
		$labelDescriptionLookup = $this->createMock( LabelDescriptionLookup::class );
		$labelDescriptionLookup->method( 'getLabel' )
			->with( new ItemId( self::BADGE_ITEM_ID ) )
			->willReturn( new Term( 'en', self::BADGE_ITEM_LABEL ) );

		return new SidebarLinkBadgeDisplay(
			$labelDescriptionLookup,
			[ self::BADGE_ITEM_ID => self::BADGE_CSS_CLASS ],
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' )
		);
	}

}
