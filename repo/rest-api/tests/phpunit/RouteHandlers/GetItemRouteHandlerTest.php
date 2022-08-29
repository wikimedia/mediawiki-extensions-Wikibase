<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\RequestData;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\RestApi\RouteHandlers\GetItemRouteHandler;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\GetItemRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	public function testHandlesUnexpectedErrors(): void {
		$useCase = $this->createStub( GetItem::class );
		$useCase->method( 'execute' )->willThrowException( new \RuntimeException() );
		$this->setService( 'WbRestApi.GetItem', $useCase );

		$routeHandler = GetItemRouteHandler::factory();
		$this->initHandler( $routeHandler, new RequestData( [ 'pathParams' => [ 'item_id' => 'Q123' ] ] ) );
		$this->validateHandler( $routeHandler );

		$response = $routeHandler->execute();
		$responseBody = json_decode( $response->getBody()->getContents() );
		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		$this->assertSame(
			ErrorResponse::UNEXPECTED_ERROR,
			$responseBody->code
		);
	}

	public function testReadWriteAccess(): void {
		$routeHandler = GetItemRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [ 'pathParams' => [ 'item_id' => 'Q123' ] ] )
		);

		$this->assertTrue( $routeHandler->needsReadAccess() );
		$this->assertFalse( $routeHandler->needsWriteAccess() );
	}
}
