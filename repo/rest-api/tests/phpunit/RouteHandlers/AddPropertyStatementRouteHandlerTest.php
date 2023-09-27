<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use Generator;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\Response;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use Throwable;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatement;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\RouteHandlers\AddPropertyStatementRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\AddPropertyStatementRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddPropertyStatementRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;
	use RestHandlerTestUtilsTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->setMockPreconditionMiddlewareFactory();
	}

	/**
	 * @dataProvider provideExceptionAndExpectedErrorCode
	 */
	public function testHandlesErrors( Throwable $exception, string $expectedErrorCode ): void {
		$useCase = $this->createStub( AddPropertyStatement::class );
		$useCase->method( 'execute' )->willThrowException( $exception );

		$this->setService( 'WbRestApi.AddPropertyStatement', $useCase );
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
	}

	private function newHandlerWithValidRequest(): AddPropertyStatementRouteHandler {
		$routeHandler = AddPropertyStatementRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'method' => 'POST',
				'headers' => [
					'User-Agent' => 'PHPUnit Test',
					'Content-Type' => 'application/json',
				],
				'pathParams' => [
					AddPropertyStatementRouteHandler::PROPERTY_ID_PATH_PARAM => 'P123',
				],
				'bodyContents' => json_encode( [
					'statement' => [
						'property' => [
							'id' => 'P1',
						],
						'value' => [
							'type' => 'novalue',
						],
					],
				] ),
			] )
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}

}
