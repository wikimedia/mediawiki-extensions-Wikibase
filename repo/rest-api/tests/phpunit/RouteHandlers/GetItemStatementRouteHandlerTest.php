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
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatement;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementFactory;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;
use Wikibase\Repo\RestApi\RouteHandlers\GetItemStatementRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\GetItemStatementRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;
	use RestHandlerTestUtilsTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->setMockChangeTagsStore();
		$this->setMockPreconditionMiddlewareFactory();
	}

	public function testValidHttpResponse(): void {
		$useCaseResponse = new GetStatementResponse( $this->createStub( Statement::class ), '20230731042031', 42 );
		$useCase = $this->createStub( GetStatement::class );
		$useCase->method( 'execute' )->willReturn( $useCaseResponse );
		$useCaseFactory = $this->createStub( GetStatementFactory::class );
		$useCaseFactory->method( 'newGetStatement' )->willReturn( $useCase );

		$this->setService( 'WbRestApi.GetStatementFactory', $useCaseFactory );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();
		$responseBody = json_decode( $response->getBody()->getContents(), true );

		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( [ 'application/json' ], $response->getHeader( 'Content-Type' ) );
		$this->assertSame( [ '"42"' ], $response->getHeader( 'ETag' ) );
		$this->assertSame( [ 'Mon, 31 Jul 2023 04:20:31 GMT' ], $response->getHeader( 'Last-Modified' ) );
		$this->assertArrayEquals( [ 'id', 'rank', 'property', 'value', 'qualifiers', 'references' ], array_keys( $responseBody ) );
	}

	/**
	 * @dataProvider provideExceptionAndExpectedErrorCode
	 */
	public function testHandlesErrors( Throwable $exception, string $expectedErrorCode ): void {
		$useCase = $this->createStub( GetStatement::class );
		$useCase->method( 'execute' )->willThrowException( $exception );
		$useCaseFactory = $this->createStub( GetStatementFactory::class );
		$useCaseFactory->method( 'newGetStatement' )->willReturn( $useCase );

		$this->setService( 'WbRestApi.GetStatementFactory', $useCaseFactory );
		$this->setService( 'WbRestApi.ErrorReporter', $this->createStub( ErrorReporter::class ) );

		/** @var Response $response */
		$response = $this->newHandlerWithValidRequest()->execute();
		$responseBody = json_decode( $response->getBody()->getContents() );

		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		$this->assertSame( $expectedErrorCode, $responseBody->code );
	}

	public function provideExceptionAndExpectedErrorCode(): Generator {
		yield 'Error handled by ResponseFactory' => [
			new UseCaseError( UseCaseError::INVALID_STATEMENT_ID, '' ),
			UseCaseError::INVALID_STATEMENT_ID,
		];

		yield 'Statement Subject Not Found' => [
			new UseCaseError( UseCaseError::STATEMENT_SUBJECT_NOT_FOUND, '', [ 'subject-id' => 'Q123' ] ),
			UseCaseError::ITEM_NOT_FOUND,
		];

		yield 'Invalid Statement Subject ID' => [
			new UseCaseError( UseCaseError::INVALID_STATEMENT_SUBJECT_ID, '', [ 'subject-id' => 'X123' ] ),
			UseCaseError::INVALID_ITEM_ID,
		];

		yield 'Item Redirect' => [ new ItemRedirect( 'Q123' ), UseCaseError::STATEMENT_NOT_FOUND ];

		yield 'Unexpected Error' => [ new RuntimeException(), UseCaseError::UNEXPECTED_ERROR ];
	}

	public function testReadWriteAccess(): void {
		$routeHandler = $this->newHandlerWithValidRequest();

		$this->assertTrue( $routeHandler->needsReadAccess() );
		$this->assertFalse( $routeHandler->needsWriteAccess() );
	}

	private function newHandlerWithValidRequest(): Handler {
		$routeHandler = GetItemStatementRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'headers' => [ 'User-Agent' => 'PHPUnit Test' ],
				'pathParams' => [
					GetItemStatementRouteHandler::ITEM_ID_PATH_PARAM => 'Q123',
					GetItemStatementRouteHandler::STATEMENT_ID_PATH_PARAM => 'Q123$some-guid',
				],
			] )
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}

}
