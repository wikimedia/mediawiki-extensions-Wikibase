<?php

/**
 * Tests for the Sites class.
 *
 * @file
 * @since 1.20
 *
 * @ingroup Site
 * @ingroup Test
 *
 * @group Site
 *
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SitesTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();
		\Wikibase\Utils::insertSitesForTests();
	}

	public function testSingleton() {
		$this->assertInstanceOf( 'Sites', Sites::singleton() );
		$this->assertTrue( Sites::singleton() === Sites::singleton() );
	}

	public function testGetSites() {
		$this->assertTrue( is_array( Sites::singleton()->getSites() ) );

	}


	public function testGetSite() {
		$count = 0;
		$sites = Sites::singleton()->getAllSites();

		foreach ( $sites as $site ) {
			$this->assertInstanceOf( 'Site', $site );

			$this->assertEquals(
				$site,
				Sites::singleton()->getSiteByGlobalId( $site->getGlobalId() )
			);

			if ( ++$count > 100 ) {
				break;
			}
		}
	}

	public function testNewSite() {
		$this->assertInstanceOf( 'Site', Sites::newSite() );
		$this->assertInstanceOf( 'Site', Sites::newSite( array() ) );
		$this->assertInstanceOf( 'Site', Sites::newSite( array( 'type' => SITE_TYPE_UNKNOWN ) ) );
	}

}
