<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\RouteHandlers;

use MediaWiki\Rest\RequestData;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\Domains\Search\RouteHandlers\RestfulSearchNotAvailableRouteHandler;

/**
 * @covers \Wikibase\Repo\Domains\Search\RouteHandlers\RestfulSearchNotAvailableRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RestfulSearchNotAvailableRouteHandlerTest extends MediaWikiIntegrationTestCase {
	use HandlerTestTrait;

	public function testHandlesSearchNotAvailable(): void {

		$routeHandler = new RestfulSearchNotAvailableRouteHandler();
		$this->initHandler(
			$routeHandler,
			new RequestData()
		);

		$response = $routeHandler->execute();
		$responseContent = json_decode( $response->getBody()->getContents() );

		self::assertSame( 'search-not-available', $responseContent->code );
		self::assertSame(
			'RESTful search is not available due to insufficient server configuration',
			$responseContent->message
		);
		self::assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
	}

}
