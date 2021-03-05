<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
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
class SiteTest extends ServiceWiringTestCase {

	public function testFromSiteLookup(): void {
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'siteGlobalID' => 'testglobalid',
				'siteLocalID' => 'testlocalid',
			] ) );
		$site = $this->createMock( Site::class );
		$site->expects( $this->once() )
			->method( 'getLocalIds' )
			->willReturn( [
				Site::ID_INTERWIKI => [ 'testlocalid' ],
				Site::ID_EQUIVALENT => [ 'testlocalid' ],
			] );
		$siteLookup = $this->createMock( SiteLookup::class );
		$siteLookup->expects( $this->once() )
			->method( 'getSite' )
			->willReturn( $site );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' )
			->willReturn( $siteLookup );
		$this->mockService( 'WikibaseClient.Logger',
			new NullLogger() );

		$this->assertSame( $site, $this->getService( 'WikibaseClient.Site' ) );
	}

	public function testFromSiteLookup_noLocalIds() {
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'siteGlobalID' => 'testglobalid',
				'siteLocalID' => 'testlocalid',
			] ) );
		$site = $this->createMock( Site::class );
		$site->method( 'getLocalIds' )
			->willReturn( [] ); // unusual but possible (T276619)
		$siteLookup = $this->createMock( SiteLookup::class );
		$siteLookup->expects( $this->once() )
			->method( 'getSite' )
			->willReturn( $site );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' )
			->willReturn( $siteLookup );
		$this->mockService( 'WikibaseClient.Logger',
			new NullLogger() );

		$this->assertSame( $site, $this->getService( 'WikibaseClient.Site' ) );
	}

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'siteGlobalID' => 'testglobalid',
				'siteLocalID' => 'testlocalid',
			] ) );
		$siteLookup = $this->createMock( SiteLookup::class );
		$siteLookup->expects( $this->once() )
			->method( 'getSite' )
			->willReturn( null );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' )
			->willReturn( $siteLookup );
		$this->mockService( 'WikibaseClient.Logger',
			new NullLogger() );

		/** @var Site $site */
		$site = $this->getService( 'WikibaseClient.Site' );

		$this->assertInstanceOf( Site::class, $site );
		$this->assertSame( 'testglobalid', $site->getGlobalId() );
		$this->assertSame(
			[
				Site::ID_INTERWIKI => [ 'testlocalid' ],
				Site::ID_EQUIVALENT => [ 'testlocalid' ],
			],
			$site->getLocalIds()
		);
	}

}
