<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Site;
use SiteLookup;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteGroupTest extends ServiceWiringTestCase {

	public function testFromSiteGroupSetting(): void {
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'siteGroup' => 'testgroup',
			] ) );

		$siteGroup = $this->getService( 'WikibaseClient.SiteGroup' );

		$this->assertSame( 'testgroup', $siteGroup );
	}

	public function testFromSiteGlobalIdSetting(): void {
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'siteGroup' => null,
				'siteGlobalID' => 'testsite',
			] ) );
		$site = $this->createMock( Site::class );
		$site->expects( $this->once() )
			->method( 'getGroup' )
			->willReturn( 'testgroup' );
		$siteLookup = $this->createMock( SiteLookup::class );
		$siteLookup->expects( $this->once() )
			->method( 'getSite' )
			->with( 'testsite' )
			->willReturn( $site );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' )
			->willReturn( $siteLookup );

		$siteGroup = $this->getService( 'WikibaseClient.SiteGroup' );

		$this->assertSame( 'testgroup', $siteGroup );
	}

	public function testFromSiteGlobalIdSetting_siteNotKnown(): void {
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'siteGroup' => null,
				'siteGlobalID' => 'testsite',
			] ) );
		$siteLookup = $this->createMock( SiteLookup::class );
		$siteLookup->expects( $this->once() )
			->method( 'getSite' )
			->with( 'testsite' )
			->willReturn( null );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' )
			->willReturn( $siteLookup );

		$siteGroup = $this->getService( 'WikibaseClient.SiteGroup' );

		$this->assertSame( Site::GROUP_NONE, $siteGroup );
	}

}
