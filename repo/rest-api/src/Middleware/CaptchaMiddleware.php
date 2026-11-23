<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Middleware;

use MediaWiki\Context\RequestContext;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\CaptchaChallenge;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class CaptchaMiddleware implements Middleware {

	public static function getBodyParamSettings(): array {
		return [
			'captcha' => [
				Handler::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_REQUIRED => false,
			],
		];
	}

	public function run( Handler $routeHandler, callable $runNext ): Response {
		$this->applyCaptchaSolutionIfAvailable( $routeHandler );

		try {
			return $runNext();
		} catch ( CaptchaChallenge $challenge ) {
			$challengeResponse = $routeHandler->getResponseFactory()->createJson( [
				'code' => 'captcha-required',
				'message' => 'A valid CAPTCHA solution is required to perform this edit',
				'context' => [ 'captcha' => $challenge->captchaChallenge ],
			] );
			$challengeResponse->setStatus( 403 );
			$challengeResponse->setHeader( 'Content-Language', 'en' );

			return $challengeResponse;
		}
	}

	private function applyCaptchaSolutionIfAvailable( Handler $routeHandler ): void {
		$captcha = $routeHandler->getValidatedBodyArray()['captcha'] ?? null;
		if ( $captcha === null ) {
			return;
		}

		$request = RequestContext::getMain()->getRequest();
		$request->setVal( 'captchaid', $captcha['id'] ?? null );
		$request->setVal( 'captchaword', $captcha['solution'] ?? null );
		if ( isset( $captcha['force_shown'] ) && $captcha['force_shown'] === true ) {
			$request->setVal( 'wgConfirmEditForceShowCaptcha', '1' );
		}
	}

}
