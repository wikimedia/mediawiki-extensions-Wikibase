<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

/**
 * @license GPL-2.0-or-later
 */
class AuthenticationMiddleware implements Middleware {

	public const USER_AUTHENTICATED_HEADER = 'X-Authenticated-User';

	public function run( Handler $routeHandler, callable $runNext ): Response {
		$response = $runNext();
		$user = $routeHandler->getAuthority()->getUser();
		if ( $user->isRegistered() ) {
			$response->setHeader( self::USER_AUTHENTICATED_HEADER, $user->getName() );
		}

		return $response;
	}
}
