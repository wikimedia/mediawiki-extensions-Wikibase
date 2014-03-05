<?php

namespace Wikibase\Test\Api;

use SiteList;
use Wikibase\Api\SiteLinkTargetProvider;

/**
 * @covers Wikibase\Api\SiteLinkTargetProvider
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class SiteLinkTargetProviderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideExpected
	 */
	public function testGetSiteList( $groups, $expectedGlobalIds ) {
		$provider = new SiteLinkTargetProvider( $this->getMockSiteStore() );

		$siteList = $provider->getSiteList( $groups );

		$this->assertEquals( count( $expectedGlobalIds ), count( $siteList ) );
		foreach( $expectedGlobalIds as $globalId ) {
			$this->assertTrue( $siteList->hasSite( $globalId ) );
		}
	}

	public static function provideExpected() {
		return array(
			//groupsToGet, siteIdsExpected
			array( array( 'foo' ), array( 'site1', 'site2' ) ),
			array( array( 'bar' ), array( 'site3' ) ),
			array( array( 'baz' ), array( 'site4' ) ),
			array( array( 'qwerty' ), array() ),
			array( array( 'foo', 'bar' ), array( 'site1', 'site2', 'site3' ) ),
			array( array( 'foo', 'baz' ), array( 'site1', 'site2', 'site4' ) ),
			array( array(), array() ),
		);
	}

	protected function getSiteList() {
		$siteList = new SiteList();
		$siteList->append( $this->getMockSite( 'site1', 'foo' ) );
		$siteList->append( $this->getMockSite( 'site2', 'foo' ) );
		$siteList->append( $this->getMockSite( 'site3', 'bar' ) );
		$siteList->append( $this->getMockSite( 'site4', 'baz' ) );
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
