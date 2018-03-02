<?php

namespace Wikibase\Repo\Interactors;

use User;
use WebRequest;

/**
 * Interactor for checking edit tokens
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class TokenCheckInteractor {

	/**
	 * @var User
	 */
	private $user;

	public function __construct( User $user ) {
		$this->user = $user;
	}

	/**
	 * Check the token sent via the given web request
	 *
	 * @param WebRequest $request
	 * @param string $tokenParam
	 * @param string|null $salt see User::matchEditToken
	 *
	 * @throws TokenCheckException If the token is not valid. The following error codes may be used
	 * with the TokenCheckException:
	 * "missingtoken" if no token was sent,
	 * "mustposttoken" if the token was not sent via POST,
	 * and "badtoken" if the token mismatches (e.g. when session data was lost).
	 */
	public function checkRequestToken( WebRequest $request, $tokenParam, $salt = null ) {
		if ( !$request->getCheck( $tokenParam ) ) {
			throw new TokenCheckException( 'Token required', 'missingtoken' );
		}

		if ( !$request->wasPosted() ) {
			throw new TokenCheckException( 'Tokens must be sent via a POST request', 'mustposttoken' );
		}

		$token = $request->getText( $tokenParam );

		if ( !$this->user->matchEditToken( $token, $salt, $request ) ) {
			throw new TokenCheckException( 'Invalid token (or loss of session data)', 'badtoken' );
		}
	}

}
