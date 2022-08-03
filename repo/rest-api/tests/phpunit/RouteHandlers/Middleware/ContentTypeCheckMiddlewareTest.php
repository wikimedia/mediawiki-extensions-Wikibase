<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\ContentTypeCheckMiddleware;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\ContentTypeCheckMiddleware
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ContentTypeCheckMiddlewareTest extends TestCase {

	public function testGivenAllowedContentType_doesNothing(): void {
		$middleware = new ContentTypeCheckMiddleware( [ ContentTypeCheckMiddleware::TYPE_APPLICATION_JSON ] );
		$request = new RequestData( [
			'headers' => [ 'Content-Type' => 'application/json' ],
		] );
		$expectedResponse = $this->createStub( Response::class );

		$response = $middleware->run(
			$this->newHandlerWithRequest( $request ),
			function () use ( $expectedResponse ) {
				return $expectedResponse;
			}
		);

		$this->assertSame( $expectedResponse, $response );
	}

	public function testGivenNotAllowedContentType_responds415(): void {
		$middleware = new ContentTypeCheckMiddleware( [ ContentTypeCheckMiddleware::TYPE_APPLICATION_JSON ] );
		$invalidContentType = 'potato';
		$request = new RequestData( [
			'headers' => [ 'Content-Type' => $invalidContentType ],
		] );
		$response = $middleware->run(
			$this->newHandlerWithRequest( $request ),
			function (): Response {
				$this->fail( 'This function should never be called in this scenario.' );
			}
		);

		$this->assertSame( 415, $response->getStatusCode() );
		$this->assertStringContainsString( $invalidContentType, $response->getBody()->getContents() );
	}

	private function newHandlerWithRequest( RequestInterface $request ): Handler {
		$handler = $this->createStub( Handler::class );
		$handler->method( 'getResponseFactory' )->willReturn( new ResponseFactory( [] ) );
		$handler->method( 'getRequest' )->willReturn( $request );

		return $handler;
	}

}
