<?php

/**
 * Tests for the MediaWikiSite class.
 *
 * @file
 * @since 1.20
 *
 * @ingroup Site
 * @ingroup Test
 *
 * @group Site
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MediaWikiSiteTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();

		static $hasSites = false;

		if ( !$hasSites ) {
			\Wikibase\Utils::insertSitesForTests();
			$hasSites = true;
		}
	}

	public function testFactoryConstruction() {
		$this->assertInstanceOf( 'MediaWikiSite', Sites::newSite( array( 'type' => SITE_TYPE_MEDIAWIKI ) ) );
		$this->assertInstanceOf( 'Site', Sites::newSite( array( 'type' => SITE_TYPE_MEDIAWIKI ) ) );
	}

	public function testNormalizePageTitle() {
		$site = Sites::newSite( array( 'type' => SITE_TYPE_MEDIAWIKI ) );

		//NOTE: this does not actually call out to the enwiki site to perform the normalization,
		//      but uses a local Title object to do so. This is hardcoded on SiteLink::normalizePageTitle
		//      for the case that MW_PHPUNIT_TEST is set.
		$this->assertEquals( "Foo", $site->normalizePageName( " foo " ) );
	}


}
