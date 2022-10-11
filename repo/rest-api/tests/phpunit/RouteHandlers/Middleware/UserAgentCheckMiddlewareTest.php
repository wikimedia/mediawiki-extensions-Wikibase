<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\RouteHandlers\Middleware\UserAgentCheckMiddleware;

/**
 * @covers \Wikibase\Repo\RestApi\RouteHandlers\Middleware\UserAgentCheckMiddleware
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UserAgentCheckMiddlewareTest extends TestCase {

	/**
	 * @dataProvider provideUserAgent
	 */
	public function testGivenNonEmptyUserAgent_doesNothing( string $userAgent ): void {
		$middleware = new UserAgentCheckMiddleware();
		$request = new RequestData( [ 'headers' => [ 'User-Agent' => $userAgent ] ] );
		$expectedResponse = $this->createStub( Response::class );

		$response = $middleware->run(
			$this->newHandlerWithRequest( $request ),
			fn(): Response => $expectedResponse
		);

		$this->assertSame( $expectedResponse, $response );
	}

	public function testGivenEmptyUserAgent_responds400(): void {
		$middleware = new UserAgentCheckMiddleware();
		$request = new RequestData( [ 'headers' => [ 'User-Agent' => '' ] ] );

		$response = $middleware->run(
			$this->newHandlerWithRequest( $request ),
			fn() => $this->fail( 'This function should never be called in this scenario.' )
		);

		$this->assertSame( 400, $response->getStatusCode() );
		$this->assertStringContainsString( 'User-Agent', $response->getBody()->getContents() );
	}

	public function testNotGivenUserAgent_responds400(): void {
		$middleware = new UserAgentCheckMiddleware();

		$response = $middleware->run(
			$this->newHandlerWithRequest( new RequestData() ),
			fn() => $this->fail( 'This function should never be called in this scenario.' )
		);

		$this->assertSame( 400, $response->getStatusCode() );
		$this->assertStringContainsString( 'User-Agent', $response->getBody()->getContents() );
	}

	public function provideUserAgent(): array {
		return [
			'CoolBot example' => [ 'CoolBot/0.0 (https://example.org/coolbot/; coolbot@example.org) generic-library/0.0' ],
			'Linux-based PC using Firefox browser' => [ 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1' ],
			'Random text' => [ 'w&9`IYF{yb;lXm|CG37t]RzKQ5FR X MSnr_&JW}75C;g"Ux/ra]7Z ]4Qv@G 6CoC+h2*LD.[P*_!;+/y0>s^JH}Q>-' ],
			'Single comma' => [ ',' ],
		];
	}

	private function newHandlerWithRequest( RequestInterface $request ): Handler {
		$handler = $this->createStub( Handler::class );
		$handler->method( 'getResponseFactory' )->willReturn( new ResponseFactory( [] ) );
		$handler->method( 'getRequest' )->willReturn( $request );

		return $handler;
	}

}
