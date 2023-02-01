<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Wikibase\Repo\RestApi\RouteHandlers\RemoveStatementRouteHandler;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatement;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\RemoveStatementRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RemoveStatementRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	public function testHandlesUnexpectedErrors(): void {
		$useCase = $this->createStub( RemoveItemStatement::class );
		$useCase->method( 'execute' )->willThrowException( new RuntimeException() );
		$this->setService( 'WbRestApi.RemoveItemStatement', $useCase );
		$this->setService( 'WbRestApi.ErrorReporter', $this->createStub( ErrorReporter::class ) );

		$routeHandler = $this->newHandlerWithValidRequest();
		$this->validateHandler( $routeHandler );

		$response = $routeHandler->execute();
		$responseBody = json_decode( $response->getBody()->getContents() );
		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		$this->assertSame( ErrorResponse::UNEXPECTED_ERROR, $responseBody->code );
	}

	public function testReadWriteAccess(): void {
		$routeHandler = $this->newHandlerWithValidRequest();

		$this->assertTrue( $routeHandler->needsReadAccess() );
		$this->assertTrue( $routeHandler->needsWriteAccess() );
	}

	private function newHandlerWithValidRequest(): Handler {
		$routeHandler = RemoveStatementRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'method' => 'DELETE',
				'headers' => [
					'User-Agent' => 'PHPUnit Test',
					'Content-Type' => 'application/json',
				],
				'pathParams' => [
					RemoveStatementRouteHandler::STATEMENT_ID_PATH_PARAM => 'Q123$some-guid',
				],
				'bodyContents' => json_encode( [
					'tags' => [ 'edit', 'tags' ],
				] ),
			] )
		);
		return $routeHandler;
	}
}
