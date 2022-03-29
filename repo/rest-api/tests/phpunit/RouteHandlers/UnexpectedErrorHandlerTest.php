<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\RouteHandlers\UnexpectedErrorHandler;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\ApiNotEnabledRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UnexpectedErrorHandlerTest extends TestCase {

	public function testHandlesError(): void {
		$errorHandler = new UnexpectedErrorHandler( new ErrorJsonPresenter() );

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

		$errorHandler = new UnexpectedErrorHandler( new ErrorJsonPresenter() );

		$response = $errorHandler->runWithErrorHandling( function ( ...$args ) use ( $expectedArgs, $expectedResponse ) {
			$this->assertSame( $expectedArgs, $args );

			return $expectedResponse;
		}, $expectedArgs );

		$this->assertSame( $expectedResponse, $response );
	}

}
