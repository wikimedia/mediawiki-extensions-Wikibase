<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Middleware;

use MediaWiki\Context\RequestContext;
use MediaWiki\Request\FauxRequest;
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

	protected function setUp(): void {
		parent::setUp();
		RequestContext::getMain()->setRequest( new FauxRequest() );
	}

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

	public function testGivenCaptchaSolution_appliesSolutionToRequest(): void {
		$captchaId = '123';
		$captchaSolution = 'potato';
		$expectedResponse = $this->createStub( Response::class );

		$response = ( new CaptchaMiddleware() )->run(
			$this->newHandler( [ 'captcha' => [ 'id' => $captchaId, 'solution' => $captchaSolution ] ] ),
			fn() => $expectedResponse,
		);

		$request = RequestContext::getMain()->getRequest();
		$this->assertSame( $captchaId, $request->getVal( 'captchaid' ) );
		$this->assertSame( $captchaSolution, $request->getVal( 'captchaword' ) );
		$this->assertSame( $expectedResponse, $response );
	}

	public function testGivenForceShownCaptchaSolution_setsForceShowCaptchaFlag(): void {
		( new CaptchaMiddleware() )->run(
			$this->newHandler( [ 'captcha' => [ 'id' => '', 'solution' => 'potato', 'force_shown' => true ] ] ),
			fn() => $this->createStub( Response::class ),
		);

		$this->assertNotNull( RequestContext::getMain()->getRequest()->getVal( 'wgConfirmEditForceShowCaptcha' ) );
	}

	/**
	 * @dataProvider notForceShownCaptchaProvider
	 */
	public function testGivenCaptchaSolutionNotForceShown_doesNotSetForceShowCaptchaFlag( array $captcha ): void {
		( new CaptchaMiddleware() )->run(
			$this->newHandler( [ 'captcha' => $captcha ] ),
			fn() => $this->createStub( Response::class ),
		);

		$this->assertNull( RequestContext::getMain()->getRequest()->getVal( 'wgConfirmEditForceShowCaptcha' ) );
	}

	public static function notForceShownCaptchaProvider(): iterable {
		yield 'force_shown missing' => [ [ 'id' => '123', 'solution' => 'potato' ] ];
		yield 'force_shown false' => [ [ 'id' => '123', 'solution' => 'potato', 'force_shown' => false ] ];
	}

	private function newHandler( array $validatedBody = [] ): Handler {
		$handler = $this->createStub( Handler::class );
		$handler->method( 'getValidatedBodyArray' )->willReturn( $validatedBody );
		$handler->method( 'getResponseFactory' )->willReturn( $this->newResponseFactory() );

		return $handler;
	}

}
