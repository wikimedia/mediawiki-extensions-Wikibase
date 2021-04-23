<?php

namespace Wikibase\Repo\Interactors;

use IContextSource;

/**
 * Interactor for checking edit tokens
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class TokenCheckInteractor {

	/**
	 * Check the token sent via the given request context
	 *
	 * @param IContextSource $context
	 * @param string $tokenParam
	 * @param string|null $salt see User::matchEditToken
	 *
	 * @throws TokenCheckException If the token is not valid. The following error codes may be used
	 * with the TokenCheckException:
	 * "missingtoken" if no token was sent,
	 * "mustposttoken" if the token was not sent via POST,
	 * and "badtoken" if the token mismatches (e.g. when session data was lost).
	 */
	public function checkRequestToken( IContextSource $context, string $tokenParam, $salt = null ): void {
		$request = $context->getRequest();

		if ( !$request->getCheck( $tokenParam ) ) {
			throw new TokenCheckException( 'Token required', 'missingtoken' );
		}

		if ( !$request->wasPosted() ) {
			throw new TokenCheckException( 'Tokens must be sent via a POST request', 'mustposttoken' );
		}

		$token = $request->getText( $tokenParam );
		$user = $context->getUser();

		if ( !$user->matchEditToken( $token, $salt, $request ) ) {
			throw new TokenCheckException( 'Invalid token (or loss of session data)', 'badtoken' );
		}
	}

}
