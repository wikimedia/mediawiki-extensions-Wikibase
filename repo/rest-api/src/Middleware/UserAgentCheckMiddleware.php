<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

/**
 * @license GPL-2.0-or-later
 */
class UserAgentCheckMiddleware implements Middleware {

	public function run( Handler $routeHandler, callable $runNext ): Response {
		$request = $routeHandler->getRequest();

		if ( $request->getHeaderLine( 'User-Agent' ) === '' ) {
			$errorResponse = $routeHandler->getResponseFactory()->createHttpError(
				400,
				[
					'code' => 'missing-user-agent',
					'message' => 'Request must include User-Agent header',
				]
			);
			$errorResponse->setHeader( 'Content-Language', 'en' );

			return $errorResponse;
		}

		return $runNext();
	}

}
