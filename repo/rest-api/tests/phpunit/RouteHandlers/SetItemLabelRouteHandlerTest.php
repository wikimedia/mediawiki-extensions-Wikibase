<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestData;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use Wikibase\Repo\RestApi\RouteHandlers\SetItemLabelRouteHandler;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\SetItemLabelRouteHandler
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class SetItemLabelRouteHandlerTest extends \MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	public function testReadWriteAccess(): void {
		$routeHandler = $this->newHandlerWithValidRequest();

		$this->assertTrue( $routeHandler->needsReadAccess() );
		$this->assertTrue( $routeHandler->needsWriteAccess() );
	}

	private function newHandlerWithValidRequest(): Handler {
		$routeHandler = SetItemLabelRouteHandler::factory();
		$this->initHandler(
			$routeHandler,
			new RequestData( [
				'headers' => [ 'User-Agent' => 'PHPUnit Test' ],
				'pathParams' => [ 'item_id' => 'Q123', 'language_code' => 'en' ],
				'bodyContents' => json_encode( [ 'label' => 'label text' ] ),
			] )
		);
		$this->validateHandler( $routeHandler );

		return $routeHandler;
	}

}
