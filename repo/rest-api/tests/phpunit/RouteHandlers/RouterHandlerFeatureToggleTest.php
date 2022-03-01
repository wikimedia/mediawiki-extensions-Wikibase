<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\RouteHandlers\ApiNotEnabledRouteHandler;
use Wikibase\Repo\RestApi\RouteHandlers\RouteHandlerFeatureToggle;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\RouteHandlerFeatureToggle
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RouterHandlerFeatureToggleTest extends TestCase {

	public function testGivenRestApiEnabled_usesGivenHandler(): void {
		$actualRouteHandler = $this->createStub( Handler::class );
		$apiNotEnabledHandler = $this->createStub( ApiNotEnabledRouteHandler::class );

		$routeHandlerFeatureToggle = new RouteHandlerFeatureToggle( true, $apiNotEnabledHandler );
		$this->assertSame(
			$actualRouteHandler,
			$routeHandlerFeatureToggle->useHandlerIfEnabled( $actualRouteHandler )
		);
	}

	public function testGivenRestApiDisabled_usesApiNotEnabledHandler(): void {
		$actualRouteHandler = $this->createStub( Handler::class );
		$apiNotEnabledHandler = $this->createStub( ApiNotEnabledRouteHandler::class );

		$routeHandlerFeatureToggle = new RouteHandlerFeatureToggle( false, $apiNotEnabledHandler );
		$this->assertSame(
			$apiNotEnabledHandler,
			$routeHandlerFeatureToggle->useHandlerIfEnabled( $actualRouteHandler )
		);
	}

}
