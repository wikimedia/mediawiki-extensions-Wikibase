<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use HashSiteStore;
use Psr\Log\NullLogger;
use Wikibase\Client\Hooks\LangLinkHandlerFactory;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
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
class LangLinkHandlerFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.LanguageLinkBadgeDisplay',
			$this->createMock( LanguageLinkBadgeDisplay::class ) );
		$this->mockService( 'WikibaseClient.NamespaceChecker',
			$this->createMock( NamespaceChecker::class ) );
		$store = $this->createMock( ClientStore::class );
		$store->expects( $this->once() )
			->method( 'getSiteLinkLookup' )
			->willReturn( new HashSiteLinkStore() );
		$this->mockService( 'WikibaseClient.Store',
			$store );
		$this->mockService( 'WikibaseClient.EntityLookup',
			new InMemoryEntityLookup() );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' )
			->willReturn( new HashSiteStore() );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getHookContainer' );
		$this->mockService( 'WikibaseClient.Logger',
			new NullLogger() );
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'siteGlobalID' => 'testwiki',
			] ) );
		$this->mockService( 'WikibaseClient.LangLinkSiteGroups',
			[ '' ] );

		$this->assertInstanceOf(
			LangLinkHandlerFactory::class,
			$this->getService( 'WikibaseClient.LangLinkHandlerFactory' )
		);
	}

}
