<?php

namespace Wikibase\Test;

use Site;
use SiteStore;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\RedirectResolvingEntityLookup;
use Wikibase\Repo\Specials\SpecialGoToLinkedPage;

/**
 * @covers Wikibase\Repo\Specials\SpecialGoToLinkedPage
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @licence GNU GPL v2+
 * @author Jan Zerebecki
 * @author Marius Hoch < hoo@online.de >
 */
class SpecialGoToLinkedPageTest extends SpecialPageTestBase {

	/**
	 * @return RedirectResolvingEntityLookup
	 */
	private function getMockRepository() {
		static $entityLookup = null;

		if ( !$entityLookup ) {
			$mockRepo = new MockRepository();
			$item = new Item( new ItemId( 'Q23' ) );
			$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'TestPageName' );

			$mockRepo->putEntity( $item );

			$entityRedirect = new EntityRedirect( new ItemId( 'Q24' ), new ItemId( 'Q23' ) );
			$mockRepo->putRedirect( $entityRedirect );

			$entityLookup = new RedirectResolvingEntityLookup( $mockRepo );
		}

		return $entityLookup;
	}

	/**
	 * @return SiteStore
	 */
	private function getMockSiteStore() {
		$mock = $this->getMock( 'SiteStore' );
		$mock->expects( $this->any() )
			->method( 'getSite' )
			->will( $this->returnCallback( function( $siteId ) {
				if ( substr( $siteId, -4 ) !== 'wiki' ) {
					return null;
				}

				$site = new Site();
				$site->setGlobalId( $siteId );
				$site->setLinkPath( 'http://'.$siteId.'.com/$1' );

				return $site;
			} ) );

		return $mock;
	}

	/**
	 * @return SpecialGoToLinkedPage
	 */
	protected function newSpecialPage() {
		$page = new SpecialGoToLinkedPage();

		$page->initServices(
			$this->getMockSiteStore(),
			$this->getMockRepository()
		);

		return $page;
	}

	public function requestWithoutRedirectProvider() {
		$cases = array();
		$cases['empty'] = array( '', null, '', '' );
		$cases['invalid'] = array( 'enwiki/invalid', null, 'enwiki', '' );
		$cases['notFound'] = array( 'enwiki/Q42', null, 'enwiki', 'Q42' );
		return $cases;
	}

	/**
	 * @dataProvider requestWithoutRedirectProvider
	 */
	public function testExecuteWithoutRedirect( $sub, $target, $site, $item ) {
		/* @var FauxResponse $response */
		list( $output, $response ) = $this->executeSpecialPage( $sub );

		$this->assertEquals( $target, $response->getheader( 'Location' ), 'Redirect' );

		$matchers = array();
		$matchers['site'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-gotolinkedpage-sitename',
				'name' => 'site',
				'value' => $site
			) );
		$matchers['itemid'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-gotolinkedpage-itemid',
				'class' => 'wb-input-text',
				'name' => 'itemid',
				'value' => $item
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-gotolinkedpage-submit',
				'class' => 'wb-input-button',
				'type' => 'submit',
				'name' => 'submit',
			) );
		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output for: ".$key );
		}
	}

	public function requestWithRedirectProvider() {
		$cases = array();
		$cases['found'] = array( 'dewiki/Q23', 'http://dewiki.com/TestPageName' );
		$cases['foundEntityRedirect'] = array( 'dewiki/Q24', 'http://dewiki.com/TestPageName' );
		$cases['foundWithSiteIdHack'] = array( 'de/Q23', 'http://dewiki.com/TestPageName' );
		return $cases;
	}

	/**
	 * @dataProvider requestWithRedirectProvider
	 */
	public function testExecuteWithRedirect( $sub, $target ) {
		/* @var FauxResponse $response */
		list( $output, $response ) = $this->executeSpecialPage( $sub );

		$this->assertEquals( $target, $response->getheader( 'Location' ), 'Redirect' );
		$this->assertEquals( '', $output );
	}

}
