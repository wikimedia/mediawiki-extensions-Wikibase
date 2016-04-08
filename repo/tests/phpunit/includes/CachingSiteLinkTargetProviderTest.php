<?php

namespace Wikibase\Tests\Repo;

use HashBagOStuff;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Site;
use SiteList;
use SiteStore;
use Wikibase\Repo\CachingSiteLinkTargetProvider;

/**
 * @covers Wikibase\Repo\CachingSiteLinkTargetProvider
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class SiteLinkTargetProviderTest extends PHPUnit_Framework_TestCase {

	public function testGetSiteList() {
		$expectedGlobalIds = array(
			'dawiki',
			'eswiki',
			'eswikiquote',
			'specieswiki',
		);

		/** @var SiteStore|PHPUnit_Framework_MockObject_MockObject $mockSiteStore */
		$mockSiteStore = $this->getMockBuilder( SiteStore::class )
			->disableOriginalConstructor()
			->getMock();
		$mockSiteStore->expects( $this->once() )
			->method( 'getSites' )
			->will( $this->returnCallback( function() {
				$siteList = new SiteList();
				$siteList->setSite( $this->newSite( 'eswiki', 'wikipedia' ) );
				$siteList->setSite( $this->newSite( 'dawiki', 'wikipedia' ) );
				$siteList->setSite( $this->newSite( 'specieswiki', 'species' ) );
				$siteList->setSite( $this->newSite( 'eswikiquote', 'wikiquote' ) );
				return $siteList;
			} ) );

		$provider = new CachingSiteLinkTargetProvider(
			$mockSiteStore,
			new HashBagOStuff(),
			array()
		);

		$siteListOne = $provider->getSiteList();
		$siteListTwo = $provider->getSiteList();

		$this->assertEquals( $siteListOne, $siteListTwo );

		$globalIds = array();
		/** @var Site $site */
		foreach ( $siteListOne as $site ) {
			$globalIds[] = $site->getGlobalId();
		}
		$this->assertSame( $expectedGlobalIds, $globalIds );
	}

	private function newSite( $globalId, $group ) {
		$site = new Site();
		$site->setGlobalId( $globalId );
		$site->setGroup( $group );
		return $site;
	}

}
