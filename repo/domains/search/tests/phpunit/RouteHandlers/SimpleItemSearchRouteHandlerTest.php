<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\RouteHandlers;

use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\Store\MatchingTermsLookupFactory;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearch;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\InLabelSearchEngine;
use Wikibase\Repo\Domains\Search\RouteHandlers\SimpleItemSearchRouteHandler;
use Wikibase\Repo\RestApi\Middleware\UnexpectedErrorHandlerMiddleware;

/**
 * @covers \Wikibase\Repo\Domains\Search\RouteHandlers\SimpleItemSearchRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SimpleItemSearchRouteHandlerTest extends MediaWikiIntegrationTestCase {
	use HandlerTestTrait;

	public function testUsesMediaWikiSearchEngine(): void {
		$this->setMwGlobals( 'wgSearchType', 'CirrusSearch' );
		$extensionRegistry = $this->createMock( ExtensionRegistry::class );
		$extensionRegistry->expects( $this->once() )->method( 'isLoaded' )
			->willReturnCallback( fn( string $extensionName ) => $extensionName === 'WikibaseCirrusSearch' );
		$this->setService( 'ExtensionRegistry', $extensionRegistry );

		$usingCirrusBasedSearch = false;
		$this->setService( 'WbSearch.InLabelSearchEngine', function() use ( &$usingCirrusBasedSearch ) {
			$usingCirrusBasedSearch = true;
			return $this->createStub( InLabelSearchEngine::class );
		} );

		$matchingTermsLookupFactory = $this->createMock( MatchingTermsLookupFactory::class );
		$matchingTermsLookupFactory->expects( $this->never() )->method( $this->anything() );
		$this->setService( 'WikibaseRepo.MatchingTermsLookupFactory', $matchingTermsLookupFactory );

		$this->assertInstanceOf( SimpleItemSearchRouteHandler::class, SimpleItemSearchRouteHandler::factory() );
		$this->assertTrue( $usingCirrusBasedSearch );
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

		$this->setService( 'WbSearch.InLabelSearchEngine', function(): void {
			$this->fail( 'WbSearch.InLabelSearchEngine was not expected to be called.' );
		} );

		$this->assertInstanceOf( SimpleItemSearchRouteHandler::class, SimpleItemSearchRouteHandler::factory() );
	}

	public function testHandlesUnexpectedErrors(): void {
		$useCase = $this->createStub( SimpleItemSearch::class );
		$useCase->method( 'execute' )->willThrowException( new RuntimeException() );

		// suppress error reporting to avoid CI failures caused by errors in the logs
		$this->setService( 'WbSearch.ErrorReporter', $this->createStub( ErrorReporter::class ) );

		$routeHandler = SimpleItemSearchRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'method' => 'GET',
				'headers' => [
					'User-Agent' => 'PHPUnit Test',
					'Content-Type' => 'application/json',
				],
				'queryParams' => [ 'language' => 'en', 'q' => 'search term' ],
				'bodyContents' => null,
			] ),
			[ 'path' => '/wikibase/v0/search/items' ]
		);
		$this->validateHandler( $routeHandler );
		$response = $routeHandler->execute();

		self::assertSame( UnexpectedErrorHandlerMiddleware::ERROR_CODE, json_decode( $response->getBody()->getContents() )->code );
		self::assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
	}

}
