<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\RouteHandlers\ResponseFactory;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\ResponseFactory
 * @covers \Wikibase\Repo\RestApi\Presentation\ErrorResponseToHttpStatus
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ResponseFactoryTest extends TestCase {

	/**
	 * @dataProvider errorCodeToHttpStatusCodeProvider
	 */
	public function testNewErrorResponse( string $errorCode, int $httpStatus ): void {
		$errorMessage = 'some-message';
		$responseBody = '{"some": "json"}';
		$errorPresenter = $this->createMock( ErrorJsonPresenter::class );
		$errorPresenter->expects( $this->once() )
			->method( 'getJson' )
			->with( $errorCode, $errorMessage )
			->willReturn( $responseBody );

		$httpResponse = ( new ResponseFactory( $errorPresenter ) )->newErrorResponse( $errorCode, $errorMessage );

		$this->assertSame( $responseBody, $httpResponse->getBody()->getContents() );
		$this->assertSame( $httpStatus, $httpResponse->getStatusCode() );
	}

	public function errorCodeToHttpStatusCodeProvider(): Generator {
		yield [ ErrorResponse::INVALID_FIELD, 400 ];
		yield [ ErrorResponse::INVALID_ITEM_ID,  400 ];
		yield [ ErrorResponse::INVALID_STATEMENT_ID,  400 ];
		yield [ ErrorResponse::INVALID_FIELD,  400 ];
		yield [ ErrorResponse::ITEM_NOT_FOUND,  404 ];
		yield [ ErrorResponse::STATEMENT_NOT_FOUND,  404 ];
		yield [ ErrorResponse::UNEXPECTED_ERROR,  500 ];
	}

	public function testGivenAuthorizationError_newErrorResponseReturnsRestWriteDenied(): void {
		$errorPresenter = $this->createMock( ErrorJsonPresenter::class );
		$errorPresenter->expects( $this->never() )->method( $this->anything() );

		$httpResponse = ( new ResponseFactory( $errorPresenter ) )
			->newErrorResponse( ErrorResponse::PERMISSION_DENIED, 'item protected' );

		$this->assertSame( 403, $httpResponse->getStatusCode() );
		$this->assertStringContainsString( 'rest-write-denied', $httpResponse->getBody()->getContents() );
	}

}
