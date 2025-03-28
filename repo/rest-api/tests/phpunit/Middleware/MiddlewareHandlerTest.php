<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Middleware;

use InvalidArgumentException;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWikiCoversValidator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Middleware\MiddlewareHandler;

/**
 * @covers \Wikibase\Repo\RestApi\Middleware\MiddlewareHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MiddlewareHandlerTest extends TestCase {
	use MediaWikiCoversValidator;

	public function testConstructorErrorsForNoMiddlewares(): void {
		$this->expectException( InvalidArgumentException::class );
		new MiddlewareHandler( [] );
	}

	public function testRun(): void {
		$runCountStart = 0;
		$expectedResponse = new Response();
		$expectedResponse->setHeader( ResponseHeaderCountingTestMiddleware::MIDDLEWARE_COUNT_HEADER, $runCountStart );

		$middlewareHandler = new MiddlewareHandler( [
			new ResponseHeaderCountingTestMiddleware( $this, $runCountStart + 2 ),
			new ResponseHeaderCountingTestMiddleware( $this, $runCountStart + 1 ),
		] );

		$response = $middlewareHandler->run(
			$this->createStub( Handler::class ),
			// The following argument is usually a reference to a Handler instance method. Using a function here for easier testing.
			fn() => $expectedResponse
		);

		$this->assertSame( $expectedResponse, $response );
	}

}
