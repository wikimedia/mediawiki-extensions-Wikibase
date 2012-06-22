<?php

namespace Wikibase\Test;
use Wikibase\Sites as Sites;
use Wikibase\SiteList as SiteList;

/**
 * Tests for the Wikibase\SiteList class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group Sites
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteListTest extends \MediaWikiTestCase {

	public function siteArrayProvider() {
		$sites = Sites::singleton()->getAllSites()->getArrayCopy();

		return array(
			array( array() ),
			array( array( array_shift( $sites ) ) ),
			array( array( array_shift( $sites ), array_shift( $sites ) ) ),
		);
	}

	/**
	 * @dataProvider siteArrayProvider
	 * @param array $siteArray
	 */
	public function testConstructor( array $siteArray ) {
		$siteList = new SiteList( $siteArray );

		$this->assertEquals( count( $siteArray ), $siteList->count() );
	}

	/**
	 * @dataProvider siteArrayProvider
	 * @param array $siteArray
	 */
	public function testIsEmpty( array $siteArray ) {
		$siteList = new SiteList( $siteArray );

		$this->assertEquals( $siteArray === array(), $siteList->isEmpty() );
	}

	public function testGetSiteByLocalId() {
		$sites = Sites::singleton()->getAllSites();
		$site = $sites->getIterator()->current();

		$this->assertEquals( $site, $sites->getSiteByLocalId( $site->getConfig()->getLocalId() ) );
	}

	public function testHasLocalId() {
		$sites = Sites::singleton()->getAllSites();
		$site = $sites->getIterator()->current();

		$this->assertTrue( $sites->hasLocalId( $site->getConfig()->getLocalId() ) );
		$this->assertFalse( $sites->hasLocalId( 'dxzfzxdegxdrfyxsdty' ) );
	}

	public function testGetSiteByGlobalId() {
		$sites = Sites::singleton()->getAllSites();
		$site = $sites->getIterator()->current();

		$this->assertEquals( $site, $sites->getSiteByGlobalId( $site->getGlobalId() ) );
	}

	public function testHasGlobalId() {
		$sites = Sites::singleton()->getAllSites();
		$site = $sites->getIterator()->current();

		$this->assertTrue( $sites->hasGlobalId( $site->getGlobalId() ) );
		$this->assertFalse( $sites->hasGlobalId( 'dxzfzxdegxdrfyxsdty' ) );
	}

	public function testGetGroup() {
		$allSites = Sites::singleton()->getAllSites();
		$count = 0;

		foreach ( $allSites->getGroupNames() as $groupName ) {
			$group = Sites::singleton()->getGroup( $groupName );
			$this->assertInstanceOf( '\Wikibase\SiteList', $group );
			$count += $group->count();

			if ( !$group->isEmpty() ) {
				$sites = iterator_to_array( $group );

				foreach ( array_slice( $sites, 0, 5 ) as $site ) {
					$this->assertInstanceOf( '\Wikibase\Site', $site );
					$this->assertEquals( $groupName, $site->getGroup() );
				}
			}
		}

		$this->assertEquals( $allSites->count(), $count );
	}

	public function testGetGroupNames() {
		$allSites = Sites::singleton()->getAllSites();
		$groups = array();

		foreach ( $allSites as $site ) {
			$groups[] = $site->getGroup();
		}

		$groups = array_values( array_unique( $groups ) );
		$obtainedGroups = array_values( $allSites->getGroupNames() );

		asort( $groups );
		asort( $obtainedGroups );

		$this->assertEquals( $groups, $obtainedGroups );
	}

	public function siteListProvider() {
		$sites = Sites::singleton();
		$groups = $sites->getAllSites()->getGroupNames();
		$group = array_shift( $groups );

		return array(
			array( $sites->getAllSites() ),
			array( $sites->getGroup( $group ), $group ),
			array( new SiteList() ),
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

		foreach ( $sites as $site ) {
			$expected[] = $site->getGlobalId();
		}

		asort( $expected );
		asort( $identifiers );

		$this->assertEquals(
			array_values( $expected ),
			array_values( $identifiers )
		);
	}

	public function testGetLocalIdentifiers() {
		$allSites = Sites::singleton()->getAllSites();
		$identifiers = $allSites->getLocalIdentifiers();

		$this->assertTrue( is_array( $identifiers ) );
	}


}