<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\CaptchaChallenge;

/**
 * @license GPL-2.0-or-later
 */
class CaptchaMiddleware implements Middleware {

	public function run( Handler $routeHandler, callable $runNext ): Response {
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

}
