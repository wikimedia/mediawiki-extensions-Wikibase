<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Wikibase\Repo\RestApi\RouteHandlers\GetStatementRouteHandler;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatement;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\GetStatementRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetStatementRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	public function testHandlesUnexpectedErrors(): void {
		$useCase = $this->createStub( GetItemStatement::class );
		$useCase->method( 'execute' )->willThrowException( new RuntimeException() );
		$this->setService( 'WbRestApi.GetItemStatement', $useCase );
		$this->setService( 'WbRestApi.ErrorReporter', $this->createStub( ErrorReporter::class ) );

		$response = $this->newHandlerWithValidRequest()->execute();
		$responseBody = json_decode( $response->getBody()->getContents() );
		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		$this->assertSame(
			ErrorResponse::UNEXPECTED_ERROR,
			$responseBody->code
		);
	}

	public function testReadWriteAccess(): void {
		$routeHandler = $this->newHandlerWithValidRequest();

		$this->assertTrue( $routeHandler->needsReadAccess() );
		$this->assertFalse( $routeHandler->needsWriteAccess() );
	}

	private function newHandlerWithValidRequest(): Handler {
		$routeHandler = GetStatementRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'headers' => [ 'User-Agent' => 'PHPUnit Test' ],
				'pathParams' => [
					'statement_id' => 'Q123$F1EF6966-6CE3-4771-ADB1-C9B6BEFBC8F9',
				],
			] )
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}

}
