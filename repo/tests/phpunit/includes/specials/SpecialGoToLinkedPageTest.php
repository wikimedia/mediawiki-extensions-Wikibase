<?php

namespace Wikibase\Test;

use FauxResponse;
use Site;
use SiteStore;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRedirectLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\Specials\SpecialGoToLinkedPage;
use Wikibase\DataModel\Entity\EntityIdParser;

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
 */
class SpecialGoToLinkedPageTest extends SpecialPageTestBase {

	/**
	 * @return SiteLinkLookup
	 */
	private function getMockSiteLinkLookup() {
		$mock = $this->getMock( 'Wikibase\Lib\Store\SiteLinkLookup' );

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
	 * @return EntityRedirectLookup
	 */
	private function getEntityRedirectLookup() {
		$mock = $this->getMock( 'Wikibase\Lib\Store\EntityRedirectLookup' );
		$mock->expects( $this->any() )
			->method( 'getRedirectForEntityId' )
			->will( $this->returnCallback( function( ItemId $itemId ) {
				if ( $itemId->getSerialization() === 'Q24' ) {
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
		$mock = $this->getMock( 'Wikibase\DataModel\Services\EntityId\EntityIdParser' );
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
		$mock = $this->getMock( 'Wikibase\Lib\Store\EntityLookup' );
		$mock->expects( $this->any() )
		->method( 'hasEntity' )
		->will( $this->returnCallback( function( ItemId $itemId ) {
			if ( $itemId->getSerialization() === 'Q23'
				|| $itemId->getSerialization() === 'Q24') {
				return true;
			} else {
				return false;
			}
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
		$cases = array();
		$cases['empty'] = array( '', null, '', '', '' );
		$cases['invalidItemID'] = array( 'enwiki/invalid', null, 'enwiki', 'invalid', 'The entered ID of the item is not valid' );
		$cases['notFound'] = array( 'enwiki/Q42', null, 'enwiki', 'Q42', 'Item was not found' );
		$cases['notFound2'] = array( 'XXwiki/Q23', null, 'XXwiki', 'Q23', 'There was no page found for that combination of item and site' );

		return $cases;
	}

	/**
	 * @dataProvider requestWithoutRedirectProvider
	 */
	public function testExecuteWithoutRedirect( $sub, $target, $site, $item, $error ) {
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
				'name' => 'submit'
			)
		);
		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output for: " . $key );
		}

		$errorMatch = array(
			'tag' => 'p',
			'content' => $error,
			'attributes' => array(
				'class' => 'error'
			)
		);

		if ( !empty( $error ) ) {
			$this->assertTag( $errorMatch, $output, "Failed to match error: " . $error );
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
