<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use BagOStuff;
use ObjectCacheFactory;
use Wikibase\Client\OtherProjectsSitesProvider;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class OtherProjectsSitesProviderTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.Settings',
			new SettingsArray( [
				'siteGlobalID' => 'testwiki',
				'specialSiteLinkGroups' => [],
			] )
		);
		$objectCacheFactory = $this->createMock( ObjectCacheFactory::class );
		$objectCacheFactory->expects( $this->once() )
			->method( 'getLocalClusterInstance' )
			->willReturn( $this->createMock( BagOStuff::class ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getObjectCacheFactory' )
			->willReturn( $objectCacheFactory );

		$this->assertInstanceOf(
			OtherProjectsSitesProvider::class,
			$this->getService( 'WikibaseClient.OtherProjectsSitesProvider' )
		);
	}

}
