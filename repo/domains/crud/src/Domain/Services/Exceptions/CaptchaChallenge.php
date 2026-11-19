<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions;

use Exception;

/**
 * @license GPL-2.0-or-later
 */
class CaptchaChallenge extends Exception {

	public function __construct( public readonly array $captchaChallenge ) {
		parent::__construct( 'Captcha solution missing or invalid' );
	}
}
