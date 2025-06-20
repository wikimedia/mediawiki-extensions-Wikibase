<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\RouteHandlers;

use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearch;
use Wikibase\Repo\Domains\Search\RouteHandlers\ItemPrefixSearchRouteHandler;
use Wikibase\Repo\Domains\Search\WbSearch;
use Wikibase\Repo\RestApi\Middleware\UnexpectedErrorHandlerMiddleware;

/**
 * @covers \Wikibase\Repo\Domains\Search\RouteHandlers\ItemPrefixSearchRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemPrefixSearchRouteHandlerTest extends MediaWikiIntegrationTestCase {
	use HandlerTestTrait;

	public function testHandlesUnexpectedErrors(): void {
		$useCase = $this->createMock( ItemPrefixSearch::class );
		$useCase->expects( $this->once() )
			->method( 'execute' )
			->willThrowException( new RuntimeException() );

		// suppress error reporting to avoid CI failures caused by errors in the logs
		$this->setService( 'WbSearch.ErrorReporter', $this->createStub( ErrorReporter::class ) );

		$routeHandler = new ItemPrefixSearchRouteHandler( $useCase, WbSearch::getMiddlewareHandler() );
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
			[ 'path' => '/wikibase/v0/suggest/items' ]
		);
		$this->validateHandler( $routeHandler );
		$response = $routeHandler->execute();

		self::assertSame( UnexpectedErrorHandlerMiddleware::ERROR_CODE, json_decode( $response->getBody()->getContents() )->code );
		self::assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
	}

}
