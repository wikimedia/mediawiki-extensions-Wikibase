<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

/**
 * @license GPL-2.0-or-later
 */
class TempUserCreationResponseHeaderMiddleware implements Middleware {

	public function run( Handler $routeHandler, callable $runNext ): Response {
		$response = $runNext();

		$user = $routeHandler->getSession()->getUser();

		// If the user is a temporary user and is not the same as the current authority user,
		// it means a new temporary user has been created. In this case, add a header to the response
		// to indicate that the temporary user was created, using the temporary user's name.
		if ( $user->isTemp() && !$routeHandler->getAuthority()->getUser()->equals( $user ) ) {
			$response->setHeader( 'X-Temporary-User-Created', $user->getName() );
		}

		return $response;
	}

}
