<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use MediaWiki\Permissions\Authority;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Session\Session;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\TempUserCreationResponseHeaderMiddleware;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\TempUserCreationResponseHeaderMiddleware
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TempUserCreationResponseHeaderMiddlewareTest extends TestCase {

	public function testAddsHeaderWhenNewTempUserIsCreatedAndDifferentFromAuthorityUser(): void {
		$user = $this->createMock( User::class );
		$user->method( 'isTemp' )->willReturn( true );
		$user->method( 'getName' )->willReturn( 'tempUser123' );

		$authority = $this->createMock( Authority::class );
		$authority->method( 'getUser' )->willReturn( new User() );

		$session = $this->createMock( Session::class );
		$session->method( 'getUser' )->willReturn( $user );

		$routeHandler = $this->createMock( SimpleHandler::class );
		$routeHandler->method( 'getSession' )->willReturn( $session );
		$routeHandler->method( 'getAuthority' )->willReturn( $authority );

		$expectedResponse = new Response();
		$middlewareResponse = ( new TempUserCreationResponseHeaderMiddleware() )->run(
			$routeHandler,
			fn() => $expectedResponse
		);

		$this->assertSame( $expectedResponse, $middlewareResponse );
		$this->assertSame( 'tempUser123', $middlewareResponse->getHeaderLine( 'X-Temporary-User-Created' ) );
	}

	public function testDoesNotAddHeaderWhenUserEqualsAuthorityUser(): void {
		$user = $this->createMock( User::class );
		$user->method( 'isTemp' )->willReturn( true );

		$authority = $this->createMock( Authority::class );
		$authority->method( 'getUser' )->willReturn( $user );

		$session = $this->createMock( Session::class );
		$session->method( 'getUser' )->willReturn( $user );

		$routeHandler = $this->createMock( SimpleHandler::class );
		$routeHandler->method( 'getSession' )->willReturn( $session );
		$routeHandler->method( 'getAuthority' )->willReturn( $authority );

		$expectedResponse = new Response();
		$middlewareResponse = ( new TempUserCreationResponseHeaderMiddleware() )->run(
			$routeHandler,
			fn() => $expectedResponse
		);

		$this->assertSame( $expectedResponse, $middlewareResponse );
		$this->assertSame( '', $middlewareResponse->getHeaderLine( 'X-Temporary-User-Created' ) );
	}

	public function testDoesNotAddHeaderWhenUserIsNotTemporary(): void {
		$user = $this->createMock( User::class );
		$user->method( 'isTemp' )->willReturn( false );

		$authority = $this->createMock( Authority::class );
		$authority->method( 'getUser' )->willReturn( new User() );

		$session = $this->createMock( Session::class );
		$session->method( 'getUser' )->willReturn( $user );

		$routeHandler = $this->createMock( SimpleHandler::class );
		$routeHandler->method( 'getSession' )->willReturn( $session );
		$routeHandler->method( 'getAuthority' )->willReturn( $authority );

		$expectedResponse = new Response();
		$middlewareResponse = ( new TempUserCreationResponseHeaderMiddleware() )->run(
			$routeHandler,
			fn() => $expectedResponse
		);

		$this->assertSame( $expectedResponse, $middlewareResponse );
		$this->assertSame( '', $middlewareResponse->getHeaderLine( 'X-Temporary-User-Created' ) );
	}
}
