<?php
/**
 * Tests for the WikibaseSites class.
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
class WikibaseSitesTests extends MediaWikiTestCase {

	public function testSingleton() {
		$sites = WikibaseSites::singleton();

		$this->assertEquals( $sites, WikibaseSites::singleton() );
		$this->assertInstanceOf( 'SeekableIterator', $sites );
	}

	public function testGetIdentifiers() {
		$this->assertTrue( is_array( WikibaseSites::singleton()->getIdentifiers() ) );

		$success = true;

		try {
			WikibaseSites::singleton()->getIdentifiers( '4241413541354135435435413' );
		}
		catch ( MWException $ex ) {
			$success = false;
		}

		$this->assertFalse( $success );
	}

	public function testHasSite() {
		$ids = WikibaseSites::singleton()->getIdentifiers();

		$this->assertTrue( WikibaseSites::singleton()->hasSite( reset( $ids ) ) );
		$this->assertFalse( WikibaseSites::singleton()->hasSite( '4241413541354135435435413' ) );
	}

	public function testGetGroup() {
		$groups = array_keys( WBSettings::get( 'siteIdentifiers' ) );
		$totalCount = WikibaseSites::singleton()->count();
		$count = 0;

		foreach ( $groups as $groupName ) {
			$group = WikibaseSites::singleton()->getGroup( $groupName );
			$this->assertInstanceOf( 'WikibaseSites', $group );
			$count += $group->count();

			if ( !$group->isEmpty() ) {
				$sites = iterator_to_array( $group );

				foreach ( array_slice( $sites, 0, 5 ) as $site ) {
					$this->assertInstanceOf( 'WikibaseSite', $site );
					$this->assertEquals( $groupName, $site->getGroup() );
				}
			}
		}

		$this->assertEquals( $totalCount, $count );
	}

	public function testGetSite() {
		$count = 0;

		foreach ( WikibaseSites::singleton() as $site ) {
			$this->assertInstanceOf( 'WikibaseSite', $site );
			$this->assertEquals( $site, WikibaseSites::singleton()->getSite( $site->getId() ) );

			if ( ++$count > 100 ) {
				break;
			}
		}
	}

}