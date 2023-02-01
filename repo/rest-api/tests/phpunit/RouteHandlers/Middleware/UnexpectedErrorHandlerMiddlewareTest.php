<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use Generator;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use TypeError;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UnexpectedErrorHandlerMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\ResponseFactory;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\UnexpectedErrorHandlerMiddleware
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UnexpectedErrorHandlerMiddlewareTest extends TestCase {

	/**
	 * @dataProvider throwableProvider
	 */
	public function testHandlesError( Throwable $throwable ): void {
		$middleware = new UnexpectedErrorHandlerMiddleware(
			new ResponseFactory( new ErrorJsonPresenter() ),
			$this->createStub( ErrorReporter::class )
		);

		$response = $middleware->run(
			$this->createStub( Handler::class ),
			function () use ( $throwable ): Response {
				throw $throwable;
			}
		);

		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		$responseBody = json_decode( $response->getBody()->getContents() );
		$this->assertSame(
			ErrorResponse::UNEXPECTED_ERROR,
			$responseBody->code
		);
	}

	public function testGivenNoError_returnsRouteResponse(): void {
		$expectedResponse = $this->createStub( Response::class );

		$middleware = new UnexpectedErrorHandlerMiddleware(
			new ResponseFactory( new ErrorJsonPresenter() ),
			$this->createStub( ErrorReporter::class )
		);

		$response = $middleware->run(
			$this->createStub( Handler::class ),
			function () use ( $expectedResponse ): Response {
				return $expectedResponse;
			}
		);

		$this->assertSame( $expectedResponse, $response );
	}

	public function testLogsExceptions(): void {
		$routeHandler = $this->createStub( Handler::class );
		$exception = new RuntimeException();
		$logger = $this->createMock( ErrorReporter::class );
		$logger->expects( $this->once() )
			->method( 'reportError' )
			->with(
				$exception,
				$routeHandler,
				$this->anything()
			);

		$middleware = new UnexpectedErrorHandlerMiddleware( new ResponseFactory( new ErrorJsonPresenter() ), $logger );
		$middleware->run(
			$routeHandler,
			function () use ( $exception ): void {
				throw $exception;
			}
		);
	}

	public function throwableProvider(): Generator {
		yield [ new TypeError() ];
		yield [ new RuntimeException() ];
	}

}
