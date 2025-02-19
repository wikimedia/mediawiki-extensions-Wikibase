<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\PreconditionMiddleware;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\RequestPreconditionCheck;
use Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\RequestPreconditionCheckResult;

/**
 * @covers \Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware\PreconditionMiddleware
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PreconditionMiddlewareTest extends TestCase {

	public function testGivenPreconditionCheckReturns412_respondsWith412(): void {
		$preconditionCheck = $this->createStub( RequestPreconditionCheck::class );
		$preconditionCheck->method( 'checkPreconditions' )->willReturn(
			RequestPreconditionCheckResult::newConditionMetResult( 123, 412 )
		);

		$middleware = new PreconditionMiddleware( $preconditionCheck );

		$response = $middleware->run(
			$this->newRouteHandler(),
			fn() => $this->fail( 'This function should never be called in this scenario.' )
		);

		$this->assertSame( 412, $response->getStatusCode() );
	}

	public function testGivenPreconditionCheckReturns304_respondsWith304(): void {
		$revId = 123;
		$preconditionCheck = $this->createStub( RequestPreconditionCheck::class );
		$preconditionCheck->method( 'checkPreconditions' )->willReturn(
			RequestPreconditionCheckResult::newConditionMetResult( 123, 304 )
		);

		$middleware = new PreconditionMiddleware( $preconditionCheck );

		$response = $middleware->run(
			$this->newRouteHandler(),
			fn() => $this->fail( 'This function should never be called in this scenario.' )
		);

		$this->assertSame( 304, $response->getStatusCode() );
		$this->assertEquals( "\"$revId\"", $response->getHeaderLine( 'ETag' ) );
	}

	public function testGivenPreconditionUnmet_doesNothing(): void {
		$preconditionCheck = $this->createStub( RequestPreconditionCheck::class );
		$preconditionCheck->method( 'checkPreconditions' )->willReturn( RequestPreconditionCheckResult::newConditionUnmetResult() );

		$middleware = new PreconditionMiddleware( $preconditionCheck );
		$expectedResponse = $this->createStub( Response::class );
		$response = $middleware->run( $this->createStub( Handler::class ), fn() => $expectedResponse );

		$this->assertSame( $expectedResponse, $response );
	}

	private function newRouteHandler(): Handler {
		$routeHandler = $this->createStub( Handler::class );
		$routeHandler->method( 'getResponseFactory' )->willReturn( new ResponseFactory( [] ) );

		return $routeHandler;
	}

}
