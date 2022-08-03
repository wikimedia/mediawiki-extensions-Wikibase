<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use MediaWiki\Permissions\Authority;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\User\UserIdentityValue;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\AuthenticationMiddleware;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\AuthenticationMiddleware
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AuthenticationMiddlewareTest extends TestCase {

	public function testGivenUnregisteredUser_doesNothing(): void {
		$middleware = new AuthenticationMiddleware();

		$response = $middleware->run(
			$this->newRouteHandlerWithUser( UserIdentityValue::newAnonymous( 'im anonymous' ) ),
			function () {
				return new Response();
			}
		);

		$this->assertFalse( $response->hasHeader( AuthenticationMiddleware::USER_AUTHENTICATED_HEADER ) );
	}

	public function testGivenRegisteredUser_addsResponseHeader(): void {
		$username = 'Potato';
		$middleware = new AuthenticationMiddleware();

		$response = $middleware->run(
			$this->newRouteHandlerWithUser( UserIdentityValue::newRegistered( 123, $username ) ),
			function () {
				return new Response();
			}
		);

		$this->assertTrue( $response->hasHeader( AuthenticationMiddleware::USER_AUTHENTICATED_HEADER ) );
		$this->assertSame(
			$username,
			$response->getHeader( AuthenticationMiddleware::USER_AUTHENTICATED_HEADER )[0]
		);
	}

	private function newRouteHandlerWithUser( UserIdentityValue $user ): Handler {
		$authority = $this->createStub( Authority::class );
		$authority->method( 'getUser' )->willReturn( $user );

		$routeHandler = $this->createStub( Handler::class );
		$routeHandler->method( 'getAuthority' )->willReturn( $authority );

		return $routeHandler;
	}

}
