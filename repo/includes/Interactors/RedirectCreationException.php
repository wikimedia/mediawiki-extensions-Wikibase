<?php

namespace Wikibase\Repo\Interactors;

use Exception;
use Wikibase\Lib\MessageException;

/**
 * Exception representing a failure to execute the "create redirect" use case.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class RedirectCreationException extends MessageException {

	/**
	 * @var string
	 */
	private $errorCode;

	/**
	 * @param string $message A free form message, for logging and debugging
	 * @param string $errorCode An error code, for use in the API
	 * @param array $params Parameters localized error message
	 * @param Exception|null $previous The previous exception that caused this exception.
	 */
	public function __construct( $message, $errorCode = '', array $params = [], Exception $previous = null ) {
		parent::__construct(
			'wikibase-redirect-' . $errorCode,
			$params,
			$message,
			$previous
		);
		$this->errorCode = $errorCode;
	}

	/**
	 * @return string
	 */
	public function getErrorCode() {
		return $this->errorCode;
	}

}
