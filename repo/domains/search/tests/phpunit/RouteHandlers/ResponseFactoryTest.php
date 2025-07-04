<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\RouteHandlers;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\RouteHandlers\ResponseFactory;

/**
 * @covers \Wikibase\Repo\Domains\Search\RouteHandlers\ResponseFactory
 * @covers \Wikibase\Repo\Domains\Search\RouteHandlers\ErrorResponseToHttpStatus
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ResponseFactoryTest extends TestCase {

	public function testNewSuccessResponse(): void {
		$httpResponse = ( new ResponseFactory() )->newSuccessResponse( [ 'contents' => 'payload' ] );

		$this->assertJsonStringEqualsJsonString(
			'{ "contents": "payload" }',
			$httpResponse->getBody()->getContents()
		);
		$this->assertEquals( [ 'application/json' ], $httpResponse->getHeader( 'Content-Type' ) );
	}

	public function testNewUseCaseErrorResponse(): void {
		$errorCode = UseCaseError::INVALID_QUERY_PARAMETER;
		$errorMessage = 'testNewUseCaseErrorResponse error message';
		$errorContext = [ 'parameter' => '_fields' ];
		$httpStatus = 400;

		$jsonErrorContext = json_encode( $errorContext );

		$httpResponse = ( new ResponseFactory() )->newUseCaseErrorResponse(
			new UseCaseError( $errorCode, $errorMessage, $errorContext )
		);

		$this->assertJsonStringEqualsJsonString(
			"{ \"code\": \"{$errorCode}\", \"message\": \"{$errorMessage}\", \"context\": $jsonErrorContext }",
			$httpResponse->getBody()->getContents()
		);
		$this->assertSame( $httpStatus, $httpResponse->getStatusCode() );
	}

	public function testNewErrorResponse(): void {
		$errorMessage = 'testNewErrorResponse error message';
		$errorCode = 'search-not-available';
		$statusCode = 500;

		$httpResponse = ( new ResponseFactory() )->newErrorResponse( $statusCode, $errorCode, $errorMessage );

		$this->assertJsonStringEqualsJsonString(
			"{ \"code\": \"{$errorCode}\", \"message\": \"{$errorMessage}\" }",
			$httpResponse->getBody()->getContents()
		);
		$this->assertSame( $statusCode, $httpResponse->getStatusCode() );
	}

	public function testGivenErrorCodeNotAssignedStatusCode_throwLogicException(): void {
		$this->expectException( LogicException::class );

		( new ResponseFactory() )->newUseCaseErrorResponse(
			new UseCaseError( 'unknown-code', 'should throw a logic exception' )
		);
	}
}
