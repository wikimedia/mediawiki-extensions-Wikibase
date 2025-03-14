<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\RouteHandlers;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\RouteHandlers\ResponseFactory;

/**
 * @covers \Wikibase\Repo\Domains\Crud\RouteHandlers\ResponseFactory
 * @covers \Wikibase\Repo\Domains\Crud\RouteHandlers\ErrorResponseToHttpStatus
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ResponseFactoryTest extends TestCase {

	/**
	 * @dataProvider errorCodeToHttpStatusCodeProvider
	 */
	public function testNewErrorResponseFromException( string $errorCode, int $httpStatus, array $errorContext = [] ): void {
		$errorMessage = 'testNewErrorResponseFromException error message';
		$jsonErrorContext = json_encode( $errorContext );

		$httpResponse = ( new ResponseFactory() )->newErrorResponseFromException(
			new UseCaseError( $errorCode, $errorMessage, $errorContext )
		);

		$this->assertJsonStringEqualsJsonString(
			$errorContext ?
				"{ \"code\": \"{$errorCode}\", \"message\": \"{$errorMessage}\", \"context\": $jsonErrorContext }" :
				"{ \"code\": \"{$errorCode}\", \"message\": \"{$errorMessage}\" }",
			$httpResponse->getBody()->getContents()
		);
		$this->assertSame( $httpStatus, $httpResponse->getStatusCode() );
	}

	/**
	 * @dataProvider errorCodeToHttpStatusCodeProvider
	 */
	public function testNewErrorResponse( string $errorCode, int $httpStatus, ?array $errorContext = null ): void {
		$errorMessage = 'testNewErrorResponse error message';
		$jsonErrorContext = json_encode( $errorContext );

		$httpResponse = ( new ResponseFactory() )->newErrorResponse( $errorCode, $errorMessage, $errorContext );

		$this->assertJsonStringEqualsJsonString(
			$errorContext ?
				"{ \"code\": \"{$errorCode}\", \"message\": \"{$errorMessage}\", \"context\": $jsonErrorContext }" :
				"{ \"code\": \"{$errorCode}\", \"message\": \"{$errorMessage}\" }",
			$httpResponse->getBody()->getContents()
		);
		$this->assertSame( $httpStatus, $httpResponse->getStatusCode() );
	}

	public static function errorCodeToHttpStatusCodeProvider(): Generator {
		yield [ UseCaseError::INVALID_QUERY_PARAMETER, 400, [ 'parameter' => '_fields' ] ];
		yield [ UseCaseError::INVALID_PATH_PARAMETER, 400, [ 'parameter' => '' ] ];
		yield [ UseCaseError::RESOURCE_NOT_FOUND, 404, [ 'resource_type' => 'statement' ] ];
	}

	public function testGivenAuthorizationError_newErrorResponseReturnsRestWriteDenied(): void {

		$httpResponse = ( new ResponseFactory() )
			->newErrorResponse( UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON, 'item protected' );

		$this->assertSame( 403, $httpResponse->getStatusCode() );
		$this->assertStringContainsString( 'rest-write-denied', $httpResponse->getBody()->getContents() );
	}

	public function testGivenErrorCodeNotAssignedStatusCode_throwLogicException(): void {
		$this->expectException( LogicException::class );

		( new ResponseFactory() )->newErrorResponse( 'unknown-code', 'should throw a logic exception' );
	}

}
