<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use HashSiteStore;
use Psr\Log\NullLogger;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\HashSiteLinkStore;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class OtherProjectsSidebarGeneratorFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray() );
		$store = $this->createMock( ClientStore::class );
		$store->expects( $this->once() )
			->method( 'getSiteLinkLookup' )
			->willReturn( new HashSiteLinkStore() );
		$this->mockService( 'WikibaseClient.Store',
			$store );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' )
			->willReturn( new HashSiteStore() );
		$this->mockService( 'WikibaseClient.EntityLookup',
			$this->createMock( EntityLookup::class ) );
		$this->mockService( 'WikibaseClient.SidebarLinkBadgeDisplay',
			$this->createMock( SidebarLinkBadgeDisplay::class ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getHookContainer' );
		$this->mockService( 'WikibaseClient.Logger',
			new NullLogger() );

		$this->assertInstanceOf(
			OtherProjectsSidebarGeneratorFactory::class,
			$this->getService( 'WikibaseClient.OtherProjectsSidebarGeneratorFactory' )
		);
	}

}
