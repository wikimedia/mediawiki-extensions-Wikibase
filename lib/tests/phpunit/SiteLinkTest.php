<?php

namespace Wikibase\Test;
use \Wikibase\SiteLink as SiteLink;
use \Wikibase\Site as Site;
use \Wikibase\Sites as Sites;

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
 * @group WikibaseItem
 * @group WikibaseLib
 * @group Database
 *
 * This test needs the Sites table and sets it up, so we need the database:
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 */
class SiteLinkTest extends \MediaWikiTestCase {

	public function setUp() {
		parent::setUp();

		static $hasSites = false;

		if ( !$hasSites ) {
			\Wikibase\Utils::insertSitesForTests();
			$hasSites = true;
		}
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
		$site = Sites::singleton()->getSiteByGlobalId( 'enwiki' );
		$link = new SiteLink( $site, "Foo" );

		$this->assertEquals( "Foo", $link->getPage() );
	}

	/**
	 * @depends testNewFromText
	 */
	public function testGetPage() {
		$link = SiteLink::newFromText( 'enwiki', "Foo" );

		$this->assertEquals( "Foo", $link->getPage() );
	}

	/**
	 * @depends testNewFromText
	 */
	public function testGetDBKey() {
		$link = SiteLink::newFromText( 'enwiki', "Foo Bar" );

		$this->assertEquals( "Foo_Bar", $link->getDBKey() );
	}

	/**
	 * @depends testNewFromText
	 */
	public function testGetSite() {
		$link = SiteLink::newFromText( 'enwiki', "Foo" );

		$expected = Sites::singleton()->getSiteByGlobalId( "enwiki" );
		$this->assertEquals( $expected, $link->getSite() );
	}

	/**
	 * @depends testNewFromText
	 */
	public function testGetSiteID() {
		$link = SiteLink::newFromText( "enwiki", "Foo" );

		$this->assertEquals( 'enwiki', $link->getSiteID() );
	}

	/**
	 * @depends testNewFromText
	 */
	public function testUrl() {
		$link = SiteLink::newFromText( 'enwiki', "Foo Bar?/notes" );
		$this->assertEquals( "https://en.wikipedia.org/wiki/Foo_Bar%3F%2Fnotes", $link->getUrl() );

		$link = SiteLink::newFromText( 'xyzwiki', "Bla" );
		$this->assertEquals( false, $link->getUrl(), "getUrl() should return false for unknown sites" );
	}

	/**
	 * @depends testNewFromText
	 */
	public function testToString() {
		$link = SiteLink::newFromText( 'enwiki', "Foo Bar" );

		$this->assertEquals( "enwiki:Foo_Bar", "$link" );
	}

}
