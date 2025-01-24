<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use MediaWiki\HookContainer\HookRunner;
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

	public function testAddsHeadersWhenNewTempUserIsCreatedAndDifferentFromAuthorityUser(): void {
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

		$hookRunner = $this->createMock( HookRunner::class );
		$hookRunner->method( 'onTempUserCreatedRedirect' )->willReturnCallback(
			function( $session, $user, $returnTo, $returnToQuery, $returnToAnchor, &$redirectUrl ): void {
				$redirectUrl = 'https://en.wikipedia.org/wiki/Test';
			}
		);

		$expectedResponse = new Response();
		$middlewareResponse = ( new TempUserCreationResponseHeaderMiddleware( $hookRunner ) )->run(
			$routeHandler,
			fn() => $expectedResponse
		);

		$this->assertSame( $expectedResponse, $middlewareResponse );
		$this->assertSame( 'tempUser123', $middlewareResponse->getHeaderLine( 'X-Temporary-User-Created' ) );
		$this->assertSame(
			'https://en.wikipedia.org/wiki/Test',
			$middlewareResponse->getHeaderLine( 'X-Temporary-User-Redirect' )
		);
	}

	public function testAddsHeaderWhenNewTempUserIsCreatedWithoutRedirectUrl(): void {
		$user = $this->createMock( User::class );
		$user->method( 'isTemp' )->willReturn( true );
		$user->method( 'getName' )->willReturn( 'tempUserWithoutRedirect' );

		$authority = $this->createMock( Authority::class );
		$authority->method( 'getUser' )->willReturn( new User() );

		$session = $this->createMock( Session::class );
		$session->method( 'getUser' )->willReturn( $user );

		$routeHandler = $this->createMock( SimpleHandler::class );
		$routeHandler->method( 'getSession' )->willReturn( $session );
		$routeHandler->method( 'getAuthority' )->willReturn( $authority );

		$hookRunner = $this->createMock( HookRunner::class );
		$hookRunner->method( 'onTempUserCreatedRedirect' )->willReturnCallback(
			function( $session, $user, $returnTo, $returnToQuery, $returnToAnchor, &$redirectUrl ): void {
				$redirectUrl = '';
			}
		);

		$expectedResponse = new Response();
		$middlewareResponse = ( new TempUserCreationResponseHeaderMiddleware( $hookRunner ) )->run(
			$routeHandler,
			fn() => $expectedResponse
		);

		$this->assertSame( $expectedResponse, $middlewareResponse );
		$this->assertSame( 'tempUserWithoutRedirect', $middlewareResponse->getHeaderLine( 'X-Temporary-User-Created' ) );
		$this->assertSame( '', $middlewareResponse->getHeaderLine( 'X-Temporary-User-Redirect' ) );
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

		$hookRunner = $this->createMock( HookRunner::class );

		$expectedResponse = new Response();
		$middlewareResponse = ( new TempUserCreationResponseHeaderMiddleware( $hookRunner ) )->run(
			$routeHandler,
			fn() => $expectedResponse
		);

		$this->assertSame( $expectedResponse, $middlewareResponse );
		$this->assertSame( '', $middlewareResponse->getHeaderLine( 'X-Temporary-User-Created' ) );
		$this->assertSame( '', $middlewareResponse->getHeaderLine( 'X-Temporary-User-Redirect' ) );
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

		$hookRunner = $this->createMock( HookRunner::class );

		$expectedResponse = new Response();
		$middlewareResponse = ( new TempUserCreationResponseHeaderMiddleware( $hookRunner ) )->run(
			$routeHandler,
			fn() => $expectedResponse
		);

		$this->assertSame( $expectedResponse, $middlewareResponse );
		$this->assertSame( '', $middlewareResponse->getHeaderLine( 'X-Temporary-User-Created' ) );
		$this->assertSame( '', $middlewareResponse->getHeaderLine( 'X-Temporary-User-Redirect' ) );
	}
}
