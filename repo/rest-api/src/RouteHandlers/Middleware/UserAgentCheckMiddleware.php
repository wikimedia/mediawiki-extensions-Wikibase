<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

/**
 * @license GPL-2.0-or-later
 */
class UserAgentCheckMiddleware implements Middleware {

	public function run( Handler $routeHandler, callable $runNext ): Response {
		$request = $routeHandler->getRequest();

		if ( $request->getHeaderLine( 'User-Agent' ) === '' ) {
			return $routeHandler->getResponseFactory()->createHttpError(
				400,
				[
					'code' => 'missing-user-agent',
					'message' => 'Request must include User-Agent header',
				]
			);
		}

		return $runNext();
	}

}
