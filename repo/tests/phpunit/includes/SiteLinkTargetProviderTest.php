<?php

namespace Wikibase\Tests\Repo;

use HashSiteStore;
use PHPUnit_Framework_TestCase;
use Site;
use SiteStore;
use Wikibase\Repo\SiteLinkTargetProvider;

/**
 * @covers Wikibase\Repo\SiteLinkTargetProvider
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Marius Hoch < hoo@online.de >
 * @author Thiemo Mättig
 */
class SiteLinkTargetProviderTest extends PHPUnit_Framework_TestCase {

	public function testGetSiteList() {
		$expectedGlobalIds = array(
			'dawiki',
			'eswiki',
			'eswikiquote',
			'specieswiki',
		);

		$provider = new SiteLinkTargetProvider(
			$this->getSiteStore(),
			array()
		);
		$siteList = $provider->getSiteList();

		$globalIds = array();
		/** @var Site $site */
		foreach ( $siteList as $site ) {
			$globalIds[] = $site->getGlobalId();
		}
		$this->assertSame( $expectedGlobalIds, $globalIds );
	}

	/**
	 * @dataProvider getSiteListProviderWithGroups
	 */
	public function testGetSiteListForGroups(
		array $groups,
		array $specialGroups,
		array $expectedGlobalIds
	) {
		$provider = new SiteLinkTargetProvider(
			$this->getSiteStore(),
			$specialGroups
		);
		$siteList = $provider->getSiteListForGroups( $groups );

		$globalIds = array();
		/** @var Site $site */
		foreach ( $siteList as $site ) {
			$globalIds[] = $site->getGlobalId();
		}
		$this->assertSame( $expectedGlobalIds, $globalIds );
	}

	public function getSiteListProviderWithGroups() {
		return array(
			array(
				array( 'wikipedia' ),
				array(),
				array( 'dawiki', 'eswiki' )
			),
			array(
				array( 'species' ), array(), array( 'specieswiki' ) ),
			array(
				array( 'wikiquote' ),
				array(),
				array( 'eswikiquote' )
			),
			array(
				array( 'qwerty' ),
				array(),
				array()
			),
			array(
				array( 'wikipedia', 'species' ),
				array(),
				array( 'dawiki', 'eswiki', 'specieswiki' )
			),
			array(
				array( 'wikipedia', 'wikiquote' ),
				array(),
				array( 'dawiki', 'eswiki', 'eswikiquote' )
			),
			array(
				array( 'special' ),
				array( 'species' ),
				array( 'specieswiki' )
			),
			array(
				array( 'wikipedia' ),
				array( 'species' ),
				array( 'dawiki', 'eswiki' )
			),
			array(
				array( 'special', 'wikipedia' ),
				array( 'species', 'wikiquote' ),
				array( 'dawiki', 'eswiki', 'eswikiquote', 'specieswiki' )
			),
			array(
				array(),
				array( 'wikipedia' ),
				array()
			),
			array(
				array(),
				array(),
				array()
			),
		);
	}

	/**
	 * @return SiteStore
	 */
	private function getSiteStore() {
		return new HashSiteStore( array(
			$this->newSite( 'eswiki', 'wikipedia' ),
			$this->newSite( 'dawiki', 'wikipedia' ),
			$this->newSite( 'specieswiki', 'species' ),
			$this->newSite( 'eswikiquote', 'wikiquote' ),
		) );
	}

	private function newSite( $globalId, $group ) {
		$site = new Site();
		$site->setGlobalId( $globalId );
		$site->setGroup( $group );
		return $site;
	}

}
