<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\PreconditionMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\RequestPreconditionCheck;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\RequestPreconditionCheckResult;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\PreconditionMiddleware
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PreconditionMiddlewareTest extends TestCase {

	public function testGivenPreconditionCheckReturns412_respondsWith412(): void {
		$preconditionCheck = $this->createStub( RequestPreconditionCheck::class );
		$preconditionCheck->method( 'checkPreconditions' )->willReturn(
			RequestPreconditionCheckResult::newConditionMetResult(
				LatestItemRevisionMetadataResult::concreteRevision( 123, '20201111070707' ),
				412
			)
		);

		$middleware = new PreconditionMiddleware( $preconditionCheck );

		$response = $middleware->run(
			$this->newRouteHandler(),
			function (): Response {
				$this->fail( 'This function should never be called in this scenario.' );
			}
		);

		$this->assertSame( 412, $response->getStatusCode() );
	}

	public function testGivenPreconditionCheckReturns304_respondsWith304(): void {
		$revId = 123;
		$preconditionCheck = $this->createStub( RequestPreconditionCheck::class );
		$preconditionCheck->method( 'checkPreconditions' )->willReturn(
			RequestPreconditionCheckResult::newConditionMetResult(
				LatestItemRevisionMetadataResult::concreteRevision( 123, '20201111070707' ),
				304
			)
		);

		$middleware = new PreconditionMiddleware( $preconditionCheck );

		$response = $middleware->run(
			$this->newRouteHandler(),
			function (): Response {
				$this->fail( 'This function should never be called in this scenario.' );
			}
		);

		$this->assertSame( 304, $response->getStatusCode() );
		$this->assertEquals( "\"$revId\"", $response->getHeaderLine( 'ETag' ) );
	}

	public function testGivenPreconditionUnmet_doesNothing(): void {
		$preconditionCheck = $this->createStub( RequestPreconditionCheck::class );
		$preconditionCheck->method( 'checkPreconditions' )->willReturn( RequestPreconditionCheckResult::newConditionUnmetResult() );

		$middleware = new PreconditionMiddleware( $preconditionCheck );
		$expectedResponse = $this->createStub( Response::class );
		$response = $middleware->run( $this->createStub( Handler::class ), function () use ( $expectedResponse ) {
			return $expectedResponse;
		} );

		$this->assertSame( $expectedResponse, $response );
	}

	private function newRouteHandler(): Handler {
		$routeHandler = $this->createStub( Handler::class );
		$routeHandler->method( 'getResponseFactory' )->willReturn( new ResponseFactory( [] ) );

		return $routeHandler;
	}

}
