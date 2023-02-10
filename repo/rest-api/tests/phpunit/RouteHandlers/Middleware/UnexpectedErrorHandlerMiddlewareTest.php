<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use Generator;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;
use TypeError;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdatePrevented;
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

	private ErrorReporter $errorReporter;
	private LoggerInterface $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->errorReporter = $this->createStub( ErrorReporter::class );
		$this->logger = new NullLogger();
	}

	/**
	 * @dataProvider throwableProvider
	 */
	public function testHandlesError( Throwable $throwable ): void {
		$response = $this->newMiddleware()->run(
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

		$response = $this->newMiddleware()->run(
			$this->createStub( Handler::class ),
			function () use ( $expectedResponse ): Response {
				return $expectedResponse;
			}
		);

		$this->assertSame( $expectedResponse, $response );
	}

	public function testReportsError(): void {
		$routeHandler = $this->createStub( Handler::class );
		$exception = new RuntimeException();
		$this->errorReporter = $this->createMock( ErrorReporter::class );
		$this->errorReporter->expects( $this->once() )
			->method( 'reportError' )
			->with(
				$exception,
				$routeHandler,
				$this->anything()
			);

		$this->newMiddleware()->run(
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

	public function testGivenEditPrevented_logsWarning(): void {
		$routeHandler = $this->createStub( Handler::class );
		$exception = new ItemUpdatePrevented( 'bad things happened' );

		$this->logger = $this->createMock( LoggerInterface::class );
		$this->logger->expects( $this->once() )
			->method( 'warning' )
			->with(
				$exception->getMessage(),
				[ 'exception' => $exception ]
			);

		$this->newMiddleware()->run(
			$routeHandler,
			function () use ( $exception ): void {
				throw $exception;
			}
		);
	}

	private function newMiddleware(): UnexpectedErrorHandlerMiddleware {
		return new UnexpectedErrorHandlerMiddleware(
			new ResponseFactory( new ErrorJsonPresenter() ),
			$this->errorReporter,
			$this->logger
		);
	}

}
