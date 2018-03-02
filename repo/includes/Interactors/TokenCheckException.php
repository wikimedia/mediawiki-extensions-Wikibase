<?php

namespace Wikibase\Repo\Interactors;

use Exception;
use Wikibase\Lib\MessageException;

/**
 * Exception representing a token check failure.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class TokenCheckException extends MessageException {

	/**
	 * @var string
	 */
	private $errorCode;

	/**
	 * @param string $message A free form message, for logging and debugging
	 * @param string $errorCode An error code, for use in the API
	 * @param Exception|null $previous The previous exception that caused this exception.
	 */
	public function __construct( $message, $errorCode = '', Exception $previous = null ) {
		parent::__construct( 'wikibase-tokencheck-' . $errorCode, [], $message, $previous );
		$this->errorCode = $errorCode;
	}

	/**
	 * @return string
	 */
	public function getErrorCode() {
		return $this->errorCode;
	}

}
