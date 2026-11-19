<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Tests\Rest\RestTestTrait;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\CaptchaChallenge;
use Wikibase\Repo\RestApi\Middleware\CaptchaMiddleware;

/**
 * @covers \Wikibase\Repo\RestApi\Middleware\CaptchaMiddleware
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CaptchaMiddlewareTest extends TestCase {

	use RestTestTrait;

	public function testGivenNoCaptchaChallenge_doesNothing(): void {
		$expectedResponse = $this->createStub( Response::class );

		$response = ( new CaptchaMiddleware() )->run(
			$this->newHandler(),
			fn() => $expectedResponse,
		);

		$this->assertSame( $expectedResponse, $response );
	}

	public function testGivenCaptchaChallenge_respondsWithChallenge(): void {
		$captchaChallenge = [ 'type' => 'simple', 'mime' => 'text/plain', 'id' => '123', 'question' => '1+1' ];

		$response = ( new CaptchaMiddleware() )->run(
			$this->newHandler(),
			fn() => throw new CaptchaChallenge( $captchaChallenge ),
		);

		$this->assertSame( 403, $response->getStatusCode() );
		$this->assertSame( [ 'en' ], $response->getHeader( 'Content-Language' ) );
		$responseBody = json_decode( $response->getBody()->getContents(), true );
		$this->assertSame( 'captcha-required', $responseBody['code'] );
		$this->assertSame( [ 'captcha' => $captchaChallenge ], $responseBody['context'] );
	}

	private function newHandler(): Handler {
		$handler = $this->createStub( Handler::class );
		$handler->method( 'getResponseFactory' )->willReturn( $this->newResponseFactory() );

		return $handler;
	}

}
