<?php

namespace Wikibase\Repo\Tests\Specials;

use Site;
use SiteList;
use SiteStore;
use SpecialPageTestBase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\Specials\SpecialItemByTitle;

/**
 * @covers Wikibase\Repo\Specials\SpecialItemByTitle
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SpecialItemByTitleTest extends SpecialPageTestBase {

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockTitleLookup() {
		$mock = $this->getMock( EntityTitleLookup::class );
		$mock->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::makeTitle( NS_MAIN, $id->getSerialization() );
			} ) );

		return $mock;
	}

	/**
	 * @return LanguageNameLookup
	 */
	private function getMockLanguageNameLookup() {
		$mock = $this->getMock( LanguageNameLookup::class );
		$mock->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( '<LANG>' ) );

		return $mock;
	}

	/**
	 * @return SiteLinkLookup
	 */
	private function getMockSiteLinkLookup() {
		$itemId = new ItemId( 'Q123' );

		$mock = $this->getMock( SiteLinkLookup::class );

		$mock->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->will( $this->returnCallback( function( $siteId, $pageName ) use ( $itemId ) {
				return $siteId === 'dewiki' ? $itemId : null;
			} ) );

		return $mock;
	}

	/**
	 * @return SiteStore
	 */
	private function getMockSiteStore() {
		$getSite = function( $siteId ) {
			$site = new Site();
			$site->setGlobalId( $siteId );
			$site->setLinkPath( "http://$siteId.com/$1" );

			return $site;
		};

		$siteList = new SiteList();
		$siteList[] = $getSite( 'dewiki' );
		$siteList[] = $getSite( 'enwiki' );

		$mock = $this->getMock( SiteStore::class );
		$mock->expects( $this->any() )
			->method( 'getSite' )
			->will( $this->returnCallback( $getSite ) );

		$mock->expects( $this->any() )
			->method( 'getSites' )
			->will( $this->returnValue( $siteList ) );

		return $mock;
	}

	/**
	 * @return SpecialItemByTitle
	 */
	protected function newSpecialPage() {
		$page = new SpecialItemByTitle();

		$page->initSettings(
			[ 'wikipedia' ]
		);

		$siteStore = $this->getMockSiteStore();

		$siteLinkTargetProvider = new SiteLinkTargetProvider( $siteStore, [] );

		$page->initServices(
			$this->getMockTitleLookup(),
			$this->getMockLanguageNameLookup(),
			$siteStore,
			$this->getMockSiteLinkLookup(),
			$siteLinkTargetProvider
		);

		return $page;
	}

	public function requestProvider() {
		$cases = [];
		$matchers = [];

		$matchers['site'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-itembytitle-sitename',
			],
			'child' => [
				'tag' => 'input',
				'attributes' => [
					'name' => 'site',
				]
			] ];
		$matchers['page'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'pagename',
			],
			'child' => [
				'tag' => 'input',
				'attributes' => [
					'name' => 'page',
				]
			] ];
		$matchers['submit'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-itembytitle-submit',
			],
			'child' => [
				'tag' => 'button',
				'attributes' => [
					'type' => 'submit',
					'name' => '',
				]
			] ];

		$cases['empty'] = [ '', null, $matchers ];

		// enwiki/NotFound  (mock returns null for everything but dewiki)
		$matchers['site']['child'][0]['attributes']['value'] = 'enwiki';
		$matchers['page']['child'][0]['attributes']['value'] = 'NotFound';

		$cases['enwiki/NotFound'] = [ 'enwiki/NotFound', null, $matchers ];

		// dewiki/Gefunden (mock returns Q123 for dewiki)
		$matchers = [];

		$cases['dewiki/Gefunden'] = [ 'dewiki/Gefunden', 'Q123', $matchers ];

		return $cases;
	}

	/**
	 * @dataProvider requestProvider
	 */
	public function testExecute( $sub, $target, array $matchers ) {
		/* @var FauxResponse $response */
		list( $output, $response ) = $this->executeSpecialPage( $sub );

		if ( $target !== null ) {
			$target = Title::newFromText( $target )->getFullURL();
			$expected = wfExpandUrl( $target, PROTO_CURRENT );
			$this->assertEquals( $expected, $response->getheader( 'Location' ), 'Redirect' );
		}

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}
