<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxResponse;
use Site;
use SiteStore;
use SpecialPageTestBase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\Specials\SpecialGoToLinkedPage;

/**
 * @covers Wikibase\Repo\Specials\SpecialGoToLinkedPage
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @license GPL-2.0+
 * @author Jan Zerebecki
 */
class SpecialGoToLinkedPageTest extends SpecialPageTestBase {

	/**
	 * @return SiteLinkLookup
	 */
	private function getMockSiteLinkLookup() {
		$mock = $this->getMock( SiteLinkLookup::class );

		$mock->expects( $this->any() )
			->method( 'getLinks' )
			->will( $this->returnCallback( function( $itemIds, $siteIds ) {
				$result = array( array( '', 'TestPageName' ) );
				if ( $siteIds === array( 'dewiki' ) && $itemIds === array( 23 ) ) {
					return $result;
				} else {
					return null;
				}
			} ) );

		return $mock;
	}

	/**
	 * @return SiteStore
	 */
	private function getMockSiteStore() {
		$mock = $this->getMock( SiteStore::class );
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
	 * @return EntityRedirectLookup
	 */
	private function getEntityRedirectLookup() {
		$mock = $this->getMock( EntityRedirectLookup::class );
		$mock->expects( $this->any() )
			->method( 'getRedirectForEntityId' )
			->will( $this->returnCallback( function( ItemId $id ) {
				if ( $id->getSerialization() === 'Q24' ) {
					return new ItemId( 'Q23' );
				} else {
					return null;
				}
			} ) );

		return $mock;
	}

	/**
	 * @return EntityIdParser
	 */
	private function getEntityIdParser() {
		$mock = $this->getMock( EntityIdParser::class );
		$mock->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnCallback( function( $itemString ) {
					return new ItemId( $itemString );
			} ) );

		return $mock;
	}

	/**
	 * @return EntityLookup
	 */
	private function getEntitylookup() {
		$mock = $this->getMock( EntityLookup::class );
		$mock->expects( $this->any() )
			->method( 'hasEntity' )
			->will( $this->returnCallback( function( ItemId $itemId ) {
				$id = $itemId->getSerialization();
				return $id === 'Q23' || $id === 'Q24';
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
			$this->getMockSiteLinkLookup(),
			$this->getEntityRedirectLookup(),
			$this->getEntityIdParser(),
			$this->getEntitylookup()
		);

		return $page;
	}

	public function requestWithoutRedirectProvider() {
		return array(
			'empty' => array( '', null, '', '', '' ),
			'invalidItemID' => array(
				'enwiki/invalid', null, 'enwiki', 'invalid',
				'(wikibase-gotolinkedpage-error-item-id-invalid)'
			),
			'notFound' => array(
				'enwiki/Q42', null, 'enwiki', 'Q42',
				'(wikibase-gotolinkedpage-error-item-not-found)'
			),
			'notFound2' => array(
				'XXwiki/Q23', null, 'XXwiki', 'Q23',
				'(wikibase-gotolinkedpage-error-page-not-found)'
			),
		);
	}

	/**
	 * @dataProvider requestWithoutRedirectProvider
	 */
	public function testExecuteWithoutRedirect( $sub, $target, $site, $item, $error ) {
		/* @var FauxResponse $response */
		list( $output, $response ) = $this->executeSpecialPage( $sub, null, 'qqx' );

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
				'name' => 'itemid',
				'value' => $item
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-gotolinkedpage-submit',
				'type' => 'submit',
				'name' => ''
			)
		);
		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output for: " . $key );
		}

		if ( !empty( $error ) ) {
			$this->assertContains( '<p class="error">' . $error . '</p>', $output,
				'Failed to match error: ' . $error );
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
