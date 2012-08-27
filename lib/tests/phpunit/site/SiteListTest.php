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

	public function testUnset() {
		$sites = Sites::singleton()->getAllSites();

		if ( !$sites->isEmpty() ) {
			$offset = $sites->getIterator()->key();
			$count = $sites->count();
			$sites->offsetUnset( $offset );
			$this->assertEquals( $count - 1, $sites->count() );
		}

		if ( !$sites->isEmpty() ) {
			$offset = $sites->getIterator()->key();
			$count = $sites->count();
			unset( $sites[$offset] );
			$this->assertEquals( $count - 1, $sites->count() );
		}

		$exception = null;
		try { $sites->offsetUnset( 'sdfsedtgsrdysftu' ); } catch ( Exception $exception ){}
		$this->assertInstanceOf( 'Exception', $exception );
	}

	public function siteArrayProvider() {
		$sites = Sites::singleton()->getAllSites()->getArrayCopy();

		$siteArrays = array( array( array() ) );

		if ( count( $sites ) > 0 ) {
			$siteArrays[] = array( array( array_shift( $sites ) ) );
		}

		if ( count( $sites ) > 1 ) {
			$siteArrays[] = array( array( array_shift( $sites ), array_shift( $sites ) ) );
		}

		return $siteArrays;
	}

	/**
	 * @dataProvider siteArrayProvider
	 * @param array $siteArray
	 */
	public function testConstructor( array $siteArray ) {
		$siteList = new SiteArray( $siteArray );

		$this->assertEquals( count( $siteArray ), $siteList->count() );
	}

	/**
	 * @dataProvider siteArrayProvider
	 * @param array $siteArray
	 */
	public function testIsEmpty( array $siteArray ) {
		$siteList = new SiteArray( $siteArray );

		$this->assertEquals( $siteArray === array(), $siteList->isEmpty() );
	}

	public function testGetSiteByLocalId() {
		$sites = Sites::singleton()->getAllSites();

		if ( $sites->isEmpty() ) {
			$this->markTestSkipped( 'No sites to test with' );
		}
		else {
			$site = $sites->getIterator()->current();
			$this->assertEquals( $site, $sites->getSiteByLocalId( $site->getConfig()->getLocalId() ) );
		}
	}

	public function testHasLocalId() {
		$sites = Sites::singleton()->getAllSites();

		if ( $sites->isEmpty() ) {
			$this->markTestSkipped( 'No sites to test with' );
		}
		else {
			$site = $sites->getIterator()->current();
			$this->assertTrue( $sites->hasLocalId( $site->getConfig()->getLocalId() ) );
			$this->assertFalse( $sites->hasLocalId( 'dxzfzxdegxdrfyxsdty' ) );
		}
	}

	public function testGetSiteByGlobalId() {
		$sites = Sites::singleton()->getAllSites();

		if ( $sites->isEmpty() ) {
			$this->markTestSkipped( 'No sites to test with' );
		}
		else {
			$site = $sites->getIterator()->current();
			$this->assertEquals( $site, $sites->getSiteByGlobalId( $site->getGlobalId() ) );
		}
	}

	public function testHasGlobalId() {
		$sites = Sites::singleton()->getAllSites();

		if ( $sites->isEmpty() ) {
			$this->markTestSkipped( 'No sites to test with' );
		}
		else {
			$site = $sites->getIterator()->current();
			$this->assertTrue( $sites->hasGlobalId( $site->getGlobalId() ) );
			$this->assertFalse( $sites->hasGlobalId( 'dxzfzxdegxdrfyxsdty' ) );
		}
	}

	public function siteListProvider() {
		$sites = Sites::singleton();
		$groups = $sites->getAllSites()->getGroupNames();
		$group = array_shift( $groups );

		return array(
			array( $sites->getAllSites() ),
			array( $sites->getGroup( $group ), $group ),
			array( new SiteArray() ),
		);
	}

	/**
	 * @dataProvider siteListProvider
	 * @param SiteList $sites
	 */
	public function testGetGlobalIdentifiers( SiteList $sites, $groupName = null ) {
		$identifiers = $sites->getGlobalIdentifiers( $groupName );

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