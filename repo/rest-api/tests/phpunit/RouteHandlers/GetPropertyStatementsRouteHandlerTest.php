<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use Generator;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Throwable;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatements;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\RouteHandlers\GetPropertyStatementsRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\GetPropertyStatementsRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementsRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	public function testValidHttpResponse(): void {
		$statementList = $this->createStub( StatementList::class );
		$useCaseResponse = new GetPropertyStatementsResponse( $statementList, '20230731042031', 42 );
		$useCase = $this->createStub( GetPropertyStatements::class );
		$useCase->method( 'execute' )->willReturn( $useCaseResponse );

		$this->setService( 'WbRestApi.GetPropertyStatements', $useCase );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();

		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( [ 'application/json' ], $response->getHeader( 'Content-Type' ) );
		$this->assertSame( [ '"42"' ], $response->getHeader( 'ETag' ) );
		$this->assertSame( [ 'Mon, 31 Jul 2023 04:20:31 GMT' ], $response->getHeader( 'Last-Modified' ) );
		$this->assertIsArray( json_decode( $response->getBody()->getContents(), true ) );
	}

	/**
	 * @dataProvider provideExceptionAndExpectedErrorCode
	 */
	public function testHandlesErrors( Throwable $exception, string $expectedErrorCode ): void {
		$useCase = $this->createStub( GetPropertyStatements::class );
		$useCase->method( 'execute' )->willThrowException( $exception );

		$this->setService( 'WbRestApi.GetPropertyStatements', $useCase );
		$this->setService( 'WbRestApi.ErrorReporter', $this->createStub( ErrorReporter::class ) );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();
		$responseBody = json_decode( $response->getBody()->getContents() );

		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		$this->assertSame( $expectedErrorCode, $responseBody->code );
	}

	public function provideExceptionAndExpectedErrorCode(): Generator {
		yield 'Error handled by ResponseFactory' => [
			new UseCaseError( UseCaseError::INVALID_PROPERTY_ID, '', [ UseCaseError::CONTEXT_PROPERTY_ID => 'X123' ] ),
			UseCaseError::INVALID_PROPERTY_ID,
		];

		yield 'Unexpected Error' => [ new RuntimeException(), UseCaseError::UNEXPECTED_ERROR ];
	}

	public function testReadWriteAccess(): void {
		$routeHandler = $this->newHandlerWithValidRequest();

		$this->assertTrue( $routeHandler->needsReadAccess() );
		$this->assertFalse( $routeHandler->needsWriteAccess() );
	}

	private function newHandlerWithValidRequest(): Handler {
		$routeHandler = GetPropertyStatementsRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'headers' => [ 'User-Agent' => 'PHPUnit Test' ],
				'pathParams' => [ 'property_id' => 'P123' ],
			] )
		);
		$this->validateHandler( $routeHandler );
		return $routeHandler;
	}

}
