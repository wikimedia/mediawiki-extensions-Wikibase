<?php

namespace Wikibase\Tests\Repo;

use SiteList;
use Wikibase\Repo\SiteLinkTargetProvider;

/**
 * @covers Wikibase\Repo\SiteLinkTargetProvider
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Marius Hoch < hoo@online.de >
 */
class SiteLinkTargetProviderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideExpected
	 */
	public function testGetSiteList( $groups, $specialGroups, $expectedGlobalIds ) {
		$provider = new SiteLinkTargetProvider( $this->getMockSiteStore(), $specialGroups );

		$siteList = $provider->getSiteList( $groups );

		$this->assertEquals( count( $expectedGlobalIds ), count( $siteList ) );
		foreach( $expectedGlobalIds as $globalId ) {
			$this->assertTrue( $siteList->hasSite( $globalId ) );
		}
	}

	public static function provideExpected() {
		return array(
			// groupsToGet, specialGroups, siteIdsExpected
			array( array( 'wikipedia' ), array(), array( 'eswiki', 'dawiki' ) ),
			array( array( 'species' ), array(), array( 'specieswiki' ) ),
			array( array( 'wikiquote' ), array(), array( 'eswikiquote' ) ),
			array( array( 'qwerty' ), array(), array() ),
			array( array( 'wikipedia', 'species' ), array(), array( 'eswiki', 'dawiki', 'specieswiki' ) ),
			array( array( 'wikipedia', 'wikiquote' ), array(), array( 'eswiki', 'dawiki', 'eswikiquote' ) ),
			array( array( 'special' ), array( 'species' ), array( 'specieswiki' ) ),
			array( array( 'wikipedia' ), array( 'species' ), array( 'eswiki', 'dawiki' ) ),
			array( array( 'special', 'wikipedia' ), array( 'species', 'wikiquote' ), array( 'eswiki', 'dawiki', 'specieswiki', 'eswikiquote' ) ),
			array( array(), array( 'wikipedia' ), array() ),
			array( array(), array(), array() ),
		);
	}

	protected function getSiteList() {
		$siteList = new SiteList();
		$siteList->append( $this->getMockSite( 'eswiki', 'wikipedia' ) );
		$siteList->append( $this->getMockSite( 'dawiki', 'wikipedia' ) );
		$siteList->append( $this->getMockSite( 'specieswiki', 'species' ) );
		$siteList->append( $this->getMockSite( 'eswikiquote', 'wikiquote' ) );
		return $siteList;
	}

	protected function getMockSiteStore() {
		$siteList = $this->getSiteList();
		$mockSiteStore = $this->getMock( 'SiteStore' );
		$mockSiteStore->expects( $this->once() )
			->method( 'getSites' )
			->will( $this->returnValue( $siteList ) );
		return $mockSiteStore;
	}

	protected function getMockSite( $globalId, $group ) {
		$mockSite = $this->getMock( 'Site' );
		$mockSite->expects( $this->once() )
			->method( 'getGroup' )
			->will( $this->returnValue( $group ) );
		$mockSite->expects( $this->any() )
			->method( 'getGlobalId' )
			->will( $this->returnValue( $globalId ) );
		$mockSite->expects( $this->any() )
			->method( 'getNavigationIds' )
			->will( $this->returnValue( array() ) );
		return $mockSite;
	}

}
