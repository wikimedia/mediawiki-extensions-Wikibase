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
 * @group WikibaseSite
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SitesTest extends \MediaWikiTestCase {

	public function testSingleton() {
		$sites = Sites::singleton();

		$this->assertEquals( $sites, Sites::singleton() );
		$this->assertInstanceOf( '\SeekableIterator', $sites );
	}

	public function testGetIdentifiers() {
		$this->assertTrue( is_array( Sites::singleton()->getIdentifiers() ) );

		$success = true;

		try {
			Sites::singleton()->getIdentifiers( '4241413541354135435435413' );
		}
		catch ( \MWException $ex ) {
			$success = false;
		}

		$this->assertFalse( $success );
	}

	public function testHasSite() {
		$ids = Sites::singleton()->getIdentifiers();

		$this->assertTrue( Sites::singleton()->hasSite( reset( $ids ) ) );
		$this->assertFalse( Sites::singleton()->hasSite( '4241413541354135435435413' ) );
	}

	public function testGetGroup() {
		$groups = array_keys( \Wikibase\Settings::get( 'siteIdentifiers' ) );
		$totalCount = Sites::singleton()->count();
		$count = 0;

		foreach ( $groups as $groupName ) {
			$group = Sites::singleton()->getGroup( $groupName );
			$this->assertInstanceOf( '\Wikibase\Sites', $group );
			$count += $group->count();

			if ( !$group->isEmpty() ) {
				$sites = iterator_to_array( $group );

				foreach ( array_slice( $sites, 0, 5 ) as $site ) {
					$this->assertInstanceOf( '\Wikibase\Site', $site );
					$this->assertEquals( $groupName, $site->getGroup() );
				}
			}
		}

		$this->assertEquals( $totalCount, $count );
	}

	public function testGetSite() {
		$count = 0;

		foreach ( Sites::singleton() as $site ) {
			$this->assertInstanceOf( '\Wikibase\Site', $site );
			$this->assertEquals( $site, Sites::singleton()->getSite( $site->getId() ) );

			if ( ++$count > 100 ) {
				break;
			}
		}
	}

}