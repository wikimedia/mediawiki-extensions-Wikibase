<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Reporter\ErrorReporter;
use MediaWiki\Rest\RequestData;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Wikibase\Repo\RestApi\RouteHandlers\GetItemStatementsRouteHandler;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatements;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\GetItemStatementsRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementsRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	public function testHandlesUnexpectedErrors(): void {
		$useCase = $this->createStub( GetItemStatements::class );
		$useCase->method( 'execute' )->willThrowException( new RuntimeException() );
		$this->setService( 'WbRestApi.GetItemStatements', $useCase );
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
		$routeHandler = GetItemStatementsRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'headers' => [ 'User-Agent' => 'PHPUnit Test' ],
				'pathParams' => [ 'item_id' => 'Q123' ],
			] )
		);
		$this->validateHandler( $routeHandler );
		return $routeHandler;
	}

}
