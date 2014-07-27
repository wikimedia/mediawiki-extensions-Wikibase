<?php

namespace Wikibase\Test;

use FauxResponse;
use Site;
use SiteStore;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Specials\SpecialTitleByItem;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers Wikibase\Repo\Specials\SpecialTitleByItem
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jan Zerebecki
 */
class SpecialTitleByItemTest extends SpecialPageTestBase {

	/**
	 * @return SiteLinkLookup
	 */
	private function getMockSiteLinkLookup() {

		$mock = $this->getMock( 'Wikibase\Lib\Store\SiteLinkLookup' );

		$mock->expects( $this->any() )
			->method( 'getLinks' )
			->will( $this->returnCallback(
				function ( $itemIds, $siteIds ) {
					$result = array(array('', 'TestPageName'));
					if ( $siteIds === array('dewiki') && $itemIds === array(23) ) {
						return $result;
					} else {
						return null;
					}
				}
			) );

		return $mock;
	}

	/**
	 * @return SiteStore
	 */
	private function getMockSiteStore() {
		$getSite = function ( $siteId ) {
			if (substr($siteId, -4) !== 'wiki') {
				return null;
			}
			$site = new Site();
			$site->setGlobalId( $siteId );
			$site->setLinkPath( 'http://'.$siteId.'.com/$1' );
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
		$page = new SpecialTitleByItem();

		$page->initSettings(
			array( 'wikipedia' )
		);

		$page->initServices(
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
				'id' => 'wb-titlebyitem-sitename',
				'name' => 'site',
			) );
		$matchers['page'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'itemid',
				'class' => 'wb-input-text',
				'name' => 'itemid',
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-titlebyitem-submit',
				'class' => 'wb-input-button',
				'type' => 'submit',
				'name' => 'submit',
			) );
		$cases['empty'] = array( '', null, $matchers );

		$matchers['site']['attributes']['value'] = 'enwiki';
		$matchers['itemid']['attributes']['value'] = '';
		$cases['invalid'] = array( 'enwiki/invalid', null, $matchers );
		$cases['notFound'] = array( 'enwiki/Q42', null, $matchers );

		$matchers = array();
		$cases['found'] = array( 'dewiki/Q23', 'http://dewiki.com/TestPageName', $matchers );
		$cases['foundWithSiteIdHack'] = array( 'de/Q23', 'http://dewiki.com/TestPageName', $matchers );

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

		$this->assertEquals( $target, $response->getheader( 'Location' ), 'Redirect' );

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}
