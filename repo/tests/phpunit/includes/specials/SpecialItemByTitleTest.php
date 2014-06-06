<?php

namespace Wikibase\Test;

use FauxResponse;
use Site;
use SiteStore;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityTitleLookup;
use Wikibase\Repo\Specials\SpecialItemByTitle;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers Wikibase\Repo\Specials\SpecialItemByTitle
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SpecialItemByTitleTest extends SpecialPageTestBase {

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockTitleLookup() {
		$mock = $this->getMock( 'Wikibase\EntityTitleLookup' );
		$mock->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback(
				function ( EntityId $id ) {
					return Title::makeTitle( NS_MAIN, $id->getSerialization() );
				}
			) );

		return $mock;
	}

	/**
	 * @return SiteLinkLookup
	 */
	private function getMockSiteLinkLookup() {
		$entityId = new ItemId( 'Q123' );

		$mock = $this->getMock( '\Wikibase\SiteLinkLookup' );

		$mock->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->will( $this->returnCallback(
				function ( $siteId, $pageName ) use ( $entityId ) {
					return ( $siteId === 'dewiki' ) ? $entityId : null;
				}
			) );

		return $mock;
	}

	/**
	 * @return SiteStore
	 */
	private function getMockSiteStore() {
		$getSite = function ( $siteId ) {
			$site = new Site();
			$site->setGlobalId( $siteId );
			$site->setLinkPath( "http://$siteId.com/$1" );
			return $site;
		};

		$mockSiteList = $this->getMock( 'SiteList' );
		$mockSiteList->expects( $this->any() )
			->method( 'getSite' )
			->will( $this->returnCallback( $getSite ) );

		$mock = $this->getMock( 'SiteStore' );
		$mock->expects( $this->any() )
			->method( 'getSite' )
			->will( $this->returnCallback( $getSite ) );

		$mock->expects( $this->any() )
			->method( 'getSites' )
			->will( $this->returnValue( $mockSiteList ) );

		return $mock;
	}

	/**
	 * @return SpecialItemByTitle
	 */
	protected function newSpecialPage() {
		$page = new SpecialItemByTitle();

		$page->initSettings(
			true,
			array( 'wikipedia' )
		);

		$page->initServices(
			$this->getMockTitleLookup(),
			$this->getMockSiteStore(),
			$this->getMockSiteLinkLookup()
		);

		return $page;
	}

	public function requestProvider() {
		$cases = array();
		$matchers = array();

		$matchers['site'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-itembytitle-sitename',
				'name' => 'site',
			) );
		$matchers['page'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'pagename',
				'class' => 'wb-input-text',
				'name' => 'page',
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-itembytitle-submit',
				'class' => 'wb-input-button',
				'type' => 'submit',
				'name' => 'submit',
			) );

		$cases['empty'] = array( '', null, $matchers );

		// enwiki/NotFound  (mock returns null for everything but dewiki)
		$matchers['site']['attributes']['value'] = 'enwiki';
		$matchers['page']['attributes']['value'] = 'NotFound';

		$cases['enwiki/NotFound'] = array( 'enwiki/NotFound', null, $matchers );

		// dewiki/Gefunden (mock returns Q123 for dewiki)
		$matchers = array();

		$cases['dewiki/Gefunden'] = array( 'dewiki/Gefunden', 'Q123', $matchers );

		return $cases;
	}

	/**
	 * @dataProvider requestProvider
	 *
	 * @param $sub
	 * @param $target
	 * @param $matchers
	 */
	public function testExecute( $sub, $target, $matchers ) {
		/* @var FauxResponse $response */
		list( $output, $response ) = $this->executeSpecialPage( $sub );

		if ( $target !== null ) {
			$target = Title::newFromText( $target );
			$this->assertEquals( $target->getFullURL(), $response->getheader( 'Location' ), 'Redirect' );
		}

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}
