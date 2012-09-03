<?php

namespace Wikibase\Test;
use \Wikibase\SiteLink as SiteLink;
use Site;
use Sites;

/**
 * Tests for the Wikibase\SiteLink class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group SiteLink
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkTest extends \MediaWikiTestCase {

	public function setUp() {
		parent::setUp();

		static $hasSites = false;

		if ( !$hasSites ) {
			\TestSites::insertIntoDb();
			$hasSites = true;
		}
	}

	/**
	 * Returns a site to test with.
	 * @return \MediaWikiSite
	 */
	protected function getSite() {
		$site = \MediaWikiSite::newFromGlobalId( 'enwiki' );
		$site->setPagePath( 'https://en.wikipedia.org/wiki/$1' );

		return $site;
	}

	protected function newFromText( $pageText ) {
		return new SiteLink( $this->getSite(), $pageText );
	}

	public function testNewFromText() {
		$link = SiteLink::newFromText( "enwiki", " foo " );
		$this->assertEquals( " foo ", $link->getPage() );

		//NOTE: this does not actually call out to the enwiki site to perform the normalization,
		//      but uses a local Title object to do so. This is hardcoded on SiteLink::normalizePageTitle
		//      for the case that MW_PHPUNIT_TEST is set.
		$link = SiteLink::newFromText( "enwiki", " foo ", true );
		$this->assertEquals( "Foo", $link->getPage() );
	}

	public function testConstructor() {
		$link = new SiteLink( $this->getSite(), "Foo" );
		$this->assertEquals( "Foo", $link->getPage() );
	}

	/**
	 * @depends testNewFromText
	 */
	public function testGetPage() {
		$link = $this->newFromText( 'Foo' );
		$this->assertEquals( "Foo", $link->getPage() );
	}

	/**
	 * @depends testNewFromText
	 */
	public function testGetSite() {
		$this->assertEquals( $this->getSite(), $this->newFromText( 'Foo' )->getSite() );
	}

	/**
	 * @depends testNewFromText
	 */
	public function testGetSiteID() {
		$this->assertEquals( 'enwiki', $this->newFromText( 'Foo' )->getSite()->getGlobalId() );
	}

	/**
	 * @depends testNewFromText
	 */
	public function testToString() {
		$link = $this->newFromText( 'Foo Bar' );

		$this->assertEquals( "[[enwiki:Foo Bar]]", "$link" );
	}

	public function dataGetSiteIDs() {
		return array(
			array(
				array(),
				array() ),

			array(
				array( SiteLink::newFromText( 'enwiki', "Foo Bar" ), SiteLink::newFromText( 'dewiki', "Bla bla" ) ),
				array( 'enwiki', 'dewiki' ) ),

			array(
				array( SiteLink::newFromText( 'enwiki', "Foo Bar" ), SiteLink::newFromText( 'dewiki', "Bla bla" ), SiteLink::newFromText( 'enwiki', "More Stuff" ) ),
				array( 'enwiki', 'dewiki' ) ),
		);
	}

	/**
	 *
	 * @dataProvider dataGetSiteIDs
	 * @depends testNewFromText
	 */
	public function testGetSiteIDs( $links, $expected ) {
		$ids = SiteLink::getSiteIDs( $links );

		$this->assertArrayEquals( $expected, $ids );
	}

	public function dataSiteLinksToArray() {
		return array(
			array(
				array(),
				array() ),

			array(
				array( SiteLink::newFromText( 'enwiki', "Foo Bar" ), SiteLink::newFromText( 'dewiki', "Bla bla" ) ),
				array( 'enwiki' => "Foo Bar", 'dewiki' => "Bla bla" ) ),

			array(
				array( SiteLink::newFromText( 'enwiki', "Foo Bar" ), SiteLink::newFromText( 'dewiki', "Bla bla" ), SiteLink::newFromText( 'enwiki', "More Stuff" ) ),
				array( 'enwiki' => "More Stuff", 'dewiki' => "Bla bla" ) ),
		);
	}

	/**
	 *
	 * @dataProvider dataSiteLinksToArray
	 * @depends testNewFromText
	 */
	public function testSiteLinksToArray( $links, $expected ) {
		$array = SiteLink::siteLinksToArray( $links );

		$this->assertArrayEquals( $expected, $array );
	}
}
