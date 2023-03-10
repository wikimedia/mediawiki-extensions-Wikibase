<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\RouteHandlers\ResponseFactory;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;

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
		$errorMessage = 'testNewErrorResponse error message';

		$httpResponse = ( new ResponseFactory() )->newErrorResponse( $errorCode, $errorMessage );

		$this->assertJsonStringEqualsJsonString(
			"{ \"code\": \"{$errorCode}\", \"message\": \"{$errorMessage}\" }",
			$httpResponse->getBody()->getContents()
		);
		$this->assertSame( $httpStatus, $httpResponse->getStatusCode() );
	}

	public function errorCodeToHttpStatusCodeProvider(): Generator {
		yield [ UseCaseException::INVALID_FIELD, 400 ];
		yield [ UseCaseException::INVALID_ITEM_ID,  400 ];
		yield [ UseCaseException::INVALID_STATEMENT_ID,  400 ];
		yield [ UseCaseException::INVALID_FIELD,  400 ];
		yield [ UseCaseException::ITEM_NOT_FOUND,  404 ];
		yield [ UseCaseException::STATEMENT_NOT_FOUND,  404 ];
		yield [ UseCaseException::UNEXPECTED_ERROR,  500 ];
	}

	public function testGivenAuthorizationError_newErrorResponseReturnsRestWriteDenied(): void {

		$httpResponse = ( new ResponseFactory() )
			->newErrorResponse( UseCaseException::PERMISSION_DENIED, 'item protected' );

		$this->assertSame( 403, $httpResponse->getStatusCode() );
		$this->assertStringContainsString( 'rest-write-denied', $httpResponse->getBody()->getContents() );
	}

}
