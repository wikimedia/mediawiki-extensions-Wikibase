<?php

namespace Wikibase\Repo\Interactors;

use Exception;

/**
 * Exception representing a failure to execute the "create redirect" use case.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class RedirectCreationException extends Exception {

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
		parent::__construct( $message, 0, $previous );
		$this->errorCode = $errorCode;
	}

	/**
	 * @return string
	 */
	public function getErrorCode() {
		return $this->errorCode;
	}

}
