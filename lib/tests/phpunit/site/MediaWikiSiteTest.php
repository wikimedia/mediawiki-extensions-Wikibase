<?php

/**
 * Tests for the Wikibase\MediaWikiSite class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group Sites
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MediaWikiSiteTest extends \MediaWikiTestCase {

	public function setUp() {
		parent::setUp();

		static $hasSites = false;

		if ( !$hasSites ) {
			\Wikibase\Utils::insertSitesForTests();
			$hasSites = true;
		}
	}

	public function testFactoryConstruction() {
		$this->assertInstanceOf( 'Wikibase\MediaWikiSite', Sites::newSite( array( 'type' => SITE_TYPE_MEDIAWIKI ) ) );
		$this->assertInstanceOf( 'Wikibase\Site', Sites::newSite( array( 'type' => SITE_TYPE_MEDIAWIKI ) ) );
	}

	public function testNormalizePageTitle() {
		$site = Sites::newSite( array( 'type' => SITE_TYPE_MEDIAWIKI ) );

		//NOTE: this does not actually call out to the enwiki site to perform the normalization,
		//      but uses a local Title object to do so. This is hardcoded on SiteLink::normalizePageTitle
		//      for the case that MW_PHPUNIT_TEST is set.
		$this->assertEquals( "Foo", $site->normalizePageName( " foo " ) );
	}


}
