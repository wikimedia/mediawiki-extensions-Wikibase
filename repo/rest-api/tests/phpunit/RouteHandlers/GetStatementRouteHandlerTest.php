<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\RequestData;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\RestApi\RouteHandlers\GetStatementRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\GetStatementRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetStatementRouteHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	public function testReadWriteAccess(): void {
		$routeHandler = GetStatementRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
					'pathParams' => [
						'statement_id' => 'Q123$F1EF6966-6CE3-4771-ADB1-C9B6BEFBC8F9'
					]
				]
			)
		);

		$this->assertTrue( $routeHandler->needsReadAccess() );
		$this->assertFalse( $routeHandler->needsWriteAccess() );
	}
}
