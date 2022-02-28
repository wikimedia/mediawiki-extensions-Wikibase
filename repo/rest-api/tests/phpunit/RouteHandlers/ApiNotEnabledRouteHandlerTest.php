<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Permissions\Authority;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\ResponseFactory;
use MediaWiki\Rest\Router;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\RouteHandlers\ApiNotEnabledRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\ApiNotEnabledRouteHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiNotEnabledRouteHandlerTest extends TestCase {

	public function testReturnsHttpErrorOnExecute(): void {
		$handler = new ApiNotEnabledRouteHandler();
		$this->initHandlerWithResponseFactory( $handler );

		$response = $handler->execute();

		$this->assertSame( 403, $response->getStatusCode() );
	}

	/**
	 * In a real API request, the ->init() call happens within MediaWiki.
	 */
	private function initHandlerWithResponseFactory( ApiNotEnabledRouteHandler $handler ): void {
		$handler->init(
			$this->createStub( Router::class ),
			$this->createStub( RequestInterface::class ),
			[],
			$this->createStub( Authority::class ),
			new ResponseFactory( [] ),
			$this->createStub( HookContainer::class )
		);
	}

}
