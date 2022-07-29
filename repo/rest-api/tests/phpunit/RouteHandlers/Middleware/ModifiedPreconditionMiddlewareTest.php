<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\ModifiedPreconditionMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\RequestPreconditionCheck;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\RequestPreconditionCheckResult;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\ModifiedPreconditionMiddleware
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ModifiedPreconditionMiddlewareTest extends TestCase {

	public function testGivenPreconditionCheckReturns412_respondsWith412(): void {
		$preconditionCheck = $this->createStub( RequestPreconditionCheck::class );
		$preconditionCheck->method( 'checkPreconditions' )->willReturn(
			RequestPreconditionCheckResult::newConditionMetResult(
				LatestItemRevisionMetadataResult::concreteRevision( 123, '20201111070707' ),
				412
			)
		);

		$middleware = new ModifiedPreconditionMiddleware( $preconditionCheck );

		$response = $middleware->run(
			$this->newHandler(),
			function (): Response {
				$this->fail( 'This function should never be called in this scenario.' );
			}
		);

		$this->assertSame( 412, $response->getStatusCode() );
	}

	public function testGivenPreconditionMismatchResult_doesNothing(): void {
		$preconditionCheck = $this->createStub( RequestPreconditionCheck::class );
		$preconditionCheck->method( 'checkPreconditions' )->willReturn( RequestPreconditionCheckResult::newConditionUnmetResult() );

		$middleware = new ModifiedPreconditionMiddleware( $preconditionCheck );
		$expectedResponse = $this->createStub( Response::class );
		$response = $middleware->run( $this->createStub( Handler::class ), function () use ( $expectedResponse ) {
			return $expectedResponse;
		} );

		$this->assertSame( $expectedResponse, $response );
	}

	private function newHandler(): Handler {
		$handler = $this->createStub( Handler::class );
		$handler->method( 'getResponseFactory' )->willReturn( new ResponseFactory( [] ) );

		return $handler;
	}

}
