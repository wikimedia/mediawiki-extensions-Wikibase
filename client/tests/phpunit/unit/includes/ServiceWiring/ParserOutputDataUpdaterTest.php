<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\HashSiteLinkStore;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ParserOutputDataUpdaterTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.OtherProjectsSidebarGeneratorFactory',
			$this->createMock( OtherProjectsSidebarGeneratorFactory::class ) );
		$store = $this->createMock( ClientStore::class );
		$store->expects( $this->once() )
			->method( 'getSiteLinkLookup' )
			->willReturn( new HashSiteLinkStore() );
		$this->mockService( 'WikibaseClient.Store',
			$store );
		$this->mockService( 'WikibaseClient.EntityLookup',
			new InMemoryEntityLookup() );
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'siteGlobalID' => 'testwiki',
			] ) );
		$this->mockService( 'WikibaseClient.Logger',
			new NullLogger() );
		$this->mockService( 'WikibaseClient.UsageAccumulatorFactory', $this->createMock( UsageAccumulatorFactory::class ) );

		$this->assertInstanceOf(
			ClientParserOutputDataUpdater::class,
			$this->getService( 'WikibaseClient.ParserOutputDataUpdater' )
		);
	}

}
