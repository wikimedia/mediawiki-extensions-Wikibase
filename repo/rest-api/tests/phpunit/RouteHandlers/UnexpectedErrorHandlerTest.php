<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\RouteHandlers\UnexpectedErrorHandler;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\UnexpectedErrorHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UnexpectedErrorHandlerTest extends TestCase {

	public function testHandlesError(): void {
		$errorHandler = new UnexpectedErrorHandler( new ErrorJsonPresenter(), new NullLogger() );

		$response = $errorHandler->runWithErrorHandling( function (): void {
			throw new \RuntimeException();
		}, [] );
		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		$responseBody = json_decode( $response->getBody()->getContents() );
		$this->assertSame(
			ErrorResponse::UNEXPECTED_ERROR,
			$responseBody->code
		);
	}

	public function testPassesParamsToCallbackAndReturnsResponse(): void {
		$expectedArgs = [ 1, 'potato' ];
		$expectedResponse = [ 'success' => true ];

		$errorHandler = new UnexpectedErrorHandler( new ErrorJsonPresenter(), new NullLogger() );

		$response = $errorHandler->runWithErrorHandling( function ( ...$args ) use ( $expectedArgs, $expectedResponse ) {
			$this->assertSame( $expectedArgs, $args );

			return $expectedResponse;
		}, $expectedArgs );

		$this->assertSame( $expectedResponse, $response );
	}

	public function testLogsExceptions(): void {
		$exception = new \RuntimeException();
		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->once() )
			->method( 'debug' )
			->with( (string)$exception );

		$errorHandler = new UnexpectedErrorHandler( new ErrorJsonPresenter(), $logger );

		$errorHandler->runWithErrorHandling( function () use ( $exception ): void {
			throw $exception;
		}, [] );
	}

}
