<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\RequestData;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\RestApi\RouteHandlers\GetItemStatementsRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\GetItemRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementsRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	public function testReadWriteAccess(): void {
		$routeHandler = GetItemStatementsRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [ 'pathParams' => [ 'id' => 'Q123' ] ] )
		);

		$this->assertTrue( $routeHandler->needsReadAccess() );
		$this->assertFalse( $routeHandler->needsWriteAccess() );
	}
}
