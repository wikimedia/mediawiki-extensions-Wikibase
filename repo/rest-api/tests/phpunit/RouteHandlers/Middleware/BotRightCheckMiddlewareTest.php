<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use MediaWiki\Permissions\Authority;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\User\UserIdentity;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Presentation\Presenters\ErrorJsonPresenter;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\BotRightCheckMiddleware;
use Wikibase\Repo\RestApi\RouteHandlers\ResponseFactory;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\BotRightCheckMiddleware
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class BotRightCheckMiddlewareTest extends TestCase {

	public function testGivenNonBotEditRequest_doesNothing(): void {
		$user = $this->createStub( UserIdentity::class );

		$expectedResponse = $this->createStub( Response::class );

		$this->assertSame(
			$expectedResponse,
			$this->newMiddleware()->run(
				$this->newRouteHandler( [], $user ),
				fn() => $expectedResponse
			)
		);
	}

	public function testGivenBotEditRequestWithInsufficientRights_returnsErrorResponse(): void {
		$user = $this->createStub( UserIdentity::class );

		$permissionManager = $this->createMock( PermissionManager::class );
		$permissionManager->expects( $this->once() )
			->method( 'userHasRight' )
			->with( $user, 'bot' )
			->willReturn( false );

		$response = $this->newMiddleware( $permissionManager )->run(
			$this->newRouteHandler( [ 'bot' => true ], $user ),
			fn() => $this->fail( 'This is not expected to be called' )
		);

		$this->assertSame( 403, $response->getStatusCode() );
	}

	public function testGivenBotEditRequestWithSufficientRights_doesNothing(): void {
		$user = $this->createStub( UserIdentity::class );

		$permissionManager = $this->createMock( PermissionManager::class );
		$permissionManager->expects( $this->once() )
			->method( 'userHasRight' )
			->with( $user, 'bot' )
			->willReturn( true );

		$expectedResponse = $this->createStub( Response::class );

		$this->assertSame(
			$expectedResponse,
			$this->newMiddleware( $permissionManager )->run(
				$this->newRouteHandler( [ 'bot' => true ], $user ),
				fn() => $expectedResponse
			)
		);
	}

	private function newMiddleware( PermissionManager $permissionManager = null ): BotRightCheckMiddleware {
		return new BotRightCheckMiddleware(
			$permissionManager ?? $this->createStub( PermissionManager::class ),
			new ResponseFactory( new ErrorJsonPresenter() )
		);
	}

	private function newRouteHandler( array $requestBody, UserIdentity $user ): Handler {
		$authority = $this->createStub( Authority::class );
		$authority->method( 'getUser' )->willReturn( $user );

		$routeHandler = $this->createStub( Handler::class );
		$routeHandler->method( 'getAuthority' )->willReturn( $authority );
		$routeHandler->method( 'getValidatedBody' )->willReturn( $requestBody );

		return $routeHandler;
	}

}
