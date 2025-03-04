<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\RouteHandlers;

use MediaWiki\Registration\ExtensionRegistry;
use MediaWikiIntegrationTestCase;
use SearchEngine;
use SearchEngineFactory;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\Store\MatchingTermsLookupFactory;
use Wikibase\Repo\Domains\Search\RouteHandlers\SimpleItemSearchRouteHandler;

/**
 * @covers \Wikibase\Repo\Domains\Search\RouteHandlers\SimpleItemSearchRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SimpleItemSearchRouteHandlerTest extends MediaWikiIntegrationTestCase {

	public function testUsesMediaWikiSearchEngine(): void {
		$this->setMwGlobals( 'wgSearchType', 'CirrusSearch' );
		$extensionRegistry = $this->createMock( ExtensionRegistry::class );
		$extensionRegistry->expects( $this->once() )->method( 'isLoaded' )
			->willReturnCallback( fn( string $extensionName ) => $extensionName === 'WikibaseCirrusSearch' );
		$this->setService( 'ExtensionRegistry', $extensionRegistry );

		$searchEngineFactory = $this->createMock( SearchEngineFactory::class );
		$searchEngineFactory->expects( $this->once() )
			->method( 'create' )
			->willReturn( $this->createStub( SearchEngine::class ) );
		$this->setService( 'SearchEngineFactory', $searchEngineFactory );

		$matchingTermsLookupFactory = $this->createMock( MatchingTermsLookupFactory::class );
		$matchingTermsLookupFactory->expects( $this->never() )
			->method( 'getLookupForSource' );
		$this->setService( 'WikibaseRepo.MatchingTermsLookupFactory', $matchingTermsLookupFactory );

		$this->assertInstanceOf( SimpleItemSearchRouteHandler::class, SimpleItemSearchRouteHandler::factory() );
	}

	public function testUsesSqlTermStoreSearchEngine(): void {
		$this->setMwGlobals( 'wgSearchType', null );
		$extensionRegistry = $this->createMock( ExtensionRegistry::class );
		$extensionRegistry->expects( $this->once() )->method( 'isLoaded' )
			->willReturn( false );
		$this->setService( 'ExtensionRegistry', $extensionRegistry );

		$matchingTermsLookupFactory = $this->createMock( MatchingTermsLookupFactory::class );
		$matchingTermsLookupFactory->expects( $this->once() )
			->method( 'getLookupForSource' )
			->willReturn( $this->createStub( MatchingTermsLookup::class ) );
		$this->setService( 'WikibaseRepo.MatchingTermsLookupFactory', $matchingTermsLookupFactory );

		$searchEngineFactory = $this->createMock( SearchEngineFactory::class );
		$searchEngineFactory->expects( $this->never() )
			->method( 'create' );
		$this->setService( 'SearchEngineFactory', $searchEngineFactory );

		$this->assertInstanceOf( SimpleItemSearchRouteHandler::class, SimpleItemSearchRouteHandler::factory() );
	}

}
