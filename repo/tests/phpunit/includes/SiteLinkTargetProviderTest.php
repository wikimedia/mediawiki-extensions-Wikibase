<?php

namespace Wikibase\Tests\Repo;

use HashSiteStore;
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
class SiteLinkTargetProviderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getSiteListProvider
	 */
	public function testGetSiteList(
		array $groups,
		array $specialGroups,
		array $expectedGlobalIds
	) {
		$provider = new SiteLinkTargetProvider( $this->getSiteStore(), $specialGroups );
		$siteList = $provider->getSiteList( $groups );

		$globalIds = array();
		/** @var Site $site */
		foreach ( $siteList as $site ) {
			$globalIds[] = $site->getGlobalId();
		}
		$this->assertSame( $expectedGlobalIds, $globalIds );
	}

	public function getSiteListProvider() {
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
		$siteStore = new HashSiteStore();

		$siteStore->saveSite( $this->newSite( 'eswiki', 'wikipedia' ) );
		$siteStore->saveSite( $this->newSite( 'dawiki', 'wikipedia' ) );
		$siteStore->saveSite( $this->newSite( 'specieswiki', 'species' ) );
		$siteStore->saveSite( $this->newSite( 'eswikiquote', 'wikiquote' ) );

		return $siteStore;
	}

	private function newSite( $globalId, $group ) {
		$site = new Site();
		$site->setGlobalId( $globalId );
		$site->setGroup( $group );
		return $site;
	}

}
