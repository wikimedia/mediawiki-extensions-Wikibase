<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\User\UserIdentityUtils;

/**
 * @license GPL-2.0-or-later
 */
class AuthenticationMiddleware implements Middleware {

	public const USER_AUTHENTICATED_HEADER = 'X-Authenticated-User';

	private UserIdentityUtils $userIdentityUtils;

	public function __construct( UserIdentityUtils $userIdentityUtils ) {
		$this->userIdentityUtils = $userIdentityUtils;
	}

	public function run( Handler $routeHandler, callable $runNext ): Response {
		$response = $runNext();
		$user = $routeHandler->getAuthority()->getUser();
		if ( $this->userIdentityUtils->isNamed( $user ) ) {
			$response->setHeader( self::USER_AUTHENTICATED_HEADER, $user->getName() );
		}

		return $response;
	}
}
