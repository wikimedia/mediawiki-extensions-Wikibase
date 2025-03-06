<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers\Middleware;

use MediaWiki\Hook\TempUserCreatedRedirectHook;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use Wikibase\Repo\RestApi\Middleware\Middleware;

/**
 * @license GPL-2.0-or-later
 */
class TempUserCreationResponseHeaderMiddleware implements Middleware {

	private TempUserCreatedRedirectHook $hookRunner;

	public function __construct( TempUserCreatedRedirectHook $hookRunner ) {
		$this->hookRunner = $hookRunner;
	}

	public function run( Handler $routeHandler, callable $runNext ): Response {
		$response = $runNext();

		$session = $routeHandler->getSession();
		$user = $session->getUser();
		$authorityUser = $routeHandler->getAuthority()->getUser();

		// If the user is a temporary user and is not the same as the current authority user,
		// it means a new temporary user has been created.
		if ( $user->isTemp() && !$authorityUser->equals( $user ) ) {
			// Hook parameters
			$returnTo = '';
			$returnToQuery = '';
			$returnToAnchor = '';
			$redirectUrl = '';

			// Call the hook
			$this->hookRunner->onTempUserCreatedRedirect(
				$session,
				$user,
				$returnTo,
				$returnToQuery,
				$returnToAnchor,
				$redirectUrl
			);

			// If redirectUrl is set, add it to the response header
			if ( $redirectUrl !== '' ) {
				$response->setHeader( 'X-Temporary-User-Redirect', $redirectUrl );
			}

			// Also add the newly created temporary user's name in the response header
			$response->setHeader( 'X-Temporary-User-Created', $user->getName() );
		}

		return $response;
	}

}
