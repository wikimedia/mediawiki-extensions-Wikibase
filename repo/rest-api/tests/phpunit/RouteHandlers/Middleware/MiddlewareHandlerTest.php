<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use InvalidArgumentException;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\MiddlewareHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\MiddlewareHandler
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\Middleware
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MiddlewareHandlerTest extends TestCase {

	public function testConstructorErrorsForNoMiddlewares(): void {
		$this->expectException( InvalidArgumentException::class );
		new MiddlewareHandler( [] );
	}

	public function testRun(): void {
		$runCountStart = 0;
		$expectedResponse = new Response();
		$expectedResponse->setHeader( ResponseHeaderCountingTestMiddleware::MIDDLEWARE_COUNT_HEADER, $runCountStart );
		$pathParams = [ 'some', 'path', 'params' ];

		$middlewareHandler = new MiddlewareHandler( [
			new ResponseHeaderCountingTestMiddleware( $this, $runCountStart + 2 ),
			new ResponseHeaderCountingTestMiddleware( $this, $runCountStart + 1 ),
		] );

		$response = $middlewareHandler->run(
			$this->createStub( Handler::class ),
			// The following argument is usually a reference to a Handler instance method. Using a function here for easier testing.
			function ( ...$args ) use ( $pathParams, $expectedResponse ) {
				$this->assertSame( $pathParams, $args );

				return $expectedResponse;
			},
			$pathParams
		);

		$this->assertSame( $expectedResponse, $response );
	}

}
