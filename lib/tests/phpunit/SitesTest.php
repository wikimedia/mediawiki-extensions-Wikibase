<?php

namespace Wikibase\Test;
use Wikibase\Item as Item;
use Wikibase\Sites as Sites;

/**
 * Tests for the Wikibase\Sites class.
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
class SitesTest extends \MediaWikiTestCase {

	public function testSingleton() {
		$sites = Sites::singleton();

		$this->assertEquals( $sites, Sites::singleton() );
	}

	public function testGetGlobalIdentifiers() {
		$this->assertTrue( is_array( Sites::singleton()->getGlobalIdentifiers() ) );
		$ids = Sites::singleton()->getGlobalIdentifiers();

		if ( $ids !== array() ) {
			$this->assertTrue( in_array( reset( $ids ), Sites::singleton()->getGlobalIdentifiers() ) );
		}

		$this->assertFalse( in_array( '4241413541354135435435413', Sites::singleton()->getGlobalIdentifiers() ) );
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

	public function testGetSite() {
		$count = 0;
		$sites = Sites::singleton()->getAllSites();

		foreach ( $sites as $site ) {
			$this->assertInstanceOf( '\Wikibase\Site', $site );

			$this->assertEquals(
				$site,
				Sites::singleton()->getSiteByGlobalId( $site->getGlobalId() )
			);

			if ( ++$count > 100 ) {
				break;
			}
		}
	}

}