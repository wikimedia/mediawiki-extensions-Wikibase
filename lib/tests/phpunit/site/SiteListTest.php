<?php

/**
 * Tests for the SiteList implementing classes.
 *
 * @file
 * @since 1.20
 *
 * @ingroup Site
 * @ingroup Test
 *
 * @group Site
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteListTest extends MediaWikiTestCase {

	/**
	 * Returns instances of SiteList implementing objects.
	 * @return array
	 */
	public function siteListProvider() {
		$sitesArrays = $this->siteArrayProvider();

		$listInstances = array();

		foreach ( $sitesArrays as $sitesArray ) {
			$listInstances[] = new SiteArray( $sitesArray[0] );
		}

		return $this->arrayWrap( $listInstances );
	}

	/**
	 * Returns arrays with instances of Site implementing objects.
	 * @return array
	 */
	public function siteArrayProvider() {
		$sites = iterator_to_array( Sites::singleton()->getSites() );

		$siteArrays = array();

		if ( count( $sites ) > 0 ) {
			$siteArrays[] = array( array_shift( $sites ) );
		}

		if ( count( $sites ) > 1 ) {
			$siteArrays[] = array( array_shift( $sites ), array_shift( $sites ) );
		}

		return $this->arrayWrap( $siteArrays );
	}

	/**
	 * @dataProvider siteListProvider
	 * @param SiteList $sites
	 */
	public function testIsEmpty( SiteList $sites ) {
		$this->assertEquals( count( $sites ) === 0, $sites->isEmpty() );
	}

	/**
	 * @dataProvider siteListProvider
	 * @param SiteList $sites
	 */
	public function testGetSiteByGlobalId( SiteList $sites ) {
		if ( $sites->isEmpty() ) {
			$this->assertTrue( true );
		}
		else {
			/**
			 * @var Site $site
			 */
			foreach ( $sites as $site ) {
				$this->assertEquals( $site, $sites->getSiteByGlobalId( $site->getGlobalId() ) );
				$this->assertEquals( $site, $sites->getSite( $site->getId() ) );
			}
		}
	}

	/**
	 * @dataProvider siteListProvider
	 * @param SiteList $sites
	 */
	public function testHasGlobalId( $sites ) {
		$this->assertFalse( $sites->hasGlobalId( 'non-existing-global-id' ) );
		$this->assertFalse( $sites->has( 72010101010 ) );

		if ( !$sites->isEmpty() ) {
			/**
			 * @var Site $site
			 */
			foreach ( $sites as $site ) {
				$this->assertTrue( $sites->hasGlobalId( $site->getGlobalId() ) );
				$this->assertTrue( $sites->has( $site->getId() ) );
			}
		}
	}

	/**
	 * @dataProvider siteListProvider
	 * @param SiteList $sites
	 */
	public function testGetGlobalIdentifiers( SiteList $sites ) {
		$identifiers = $sites->getGlobalIdentifiers();

		$this->assertTrue( is_array( $identifiers ) );

		$expected = array();

		/**
		 * @var Site $site
		 */
		foreach ( $sites as $site ) {
			$expected[] = $site->getGlobalId();
		}

		$this->assertArrayEquals( $expected, $identifiers );
	}


}