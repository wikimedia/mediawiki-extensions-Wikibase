<?php

namespace Wikibase\Test;

use MediaWikiSite;
use Site;
use Wikibase\SiteLink;

/**
 * @covers Wikibase\SiteLink
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseDataModel
 * @group SiteLink
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( Site $site, $pageName ) {
		$link = new SiteLink( $site, $pageName );

		$this->assertEquals( $site, $link->getSite() );
		$this->assertEquals( $pageName, $link->getPage() );
	}

	public function constructorProvider() {
		$argLists = array();

		foreach ( $this->getSites() as $site ) {
			foreach ( array( 'Nyan', 'Nyan!', 'Main_Page' ) as $pageName ) {
				$argLists[] = array( $site, $pageName );
			}
		}

		return $argLists;
	}

	protected function getSites() {
		$sites = array();

		$enWiki = new MediaWikiSite();
		$enWiki->setGlobalId( 'enwiki' );

		$sites[] = $enWiki;


		$nlWiki = new MediaWikiSite();
		$nlWiki->setGlobalId( 'nlwiki' );

		$sites[] = $nlWiki;


		$fooWiki = new Site();
		$fooWiki->setGlobalId( 'foobarbaz' );

		$sites[] = $fooWiki;

		return $sites;
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testToString( Site $site, $pageName ) {
		$link = new SiteLink( $site, $pageName );

		$this->assertInternalType( 'string', (string)$link );

		$parts = explode( ':', (string)$link );
		$this->assertCount( 2, $parts, 'string representation should contain one colon' );

		$this->assertEquals(
			$site->getGlobalId(),
			substr( $parts[0], 2 ),
			'The first part of the string representation should be [[$globalSiteId'
		);

		$this->assertEquals(
			$pageName,
			substr( $parts[1], 0, strlen( $parts[1] ) - 2 ),
			'The second part of the string representation should be $pageName]]'
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testGetUrl( Site $site, $pageName ) {
		$link = new SiteLink( $site, $pageName );

		$this->assertEquals(
			$site->getPageUrl( $pageName ),
			$link->getUrl()
		);
	}

	/**
	 * @dataProvider siteProvider
	 */
	public function testConstructorWithNullPageName( Site $site ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SiteLink( $site, null );
	}

	public function siteProvider() {
		$argLists = array();

		foreach ( $this->getSites() as $site ) {
			$argLists[] = array( $site );
		}

		return $argLists;
	}

}
