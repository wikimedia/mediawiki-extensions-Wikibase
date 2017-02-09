<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

/**
 * Thrown from ChangeOpDeserializers to be handled by a higher abstraction layer such as the API
 *
 * @license GPL-2.0+
 */
class ChangeOpDeserializationException extends \InvalidArgumentException {

	/**
	 * @var string
	 */
	private $errorCode;

	/**
	 * @param string $message - descriptive error message to be used in logs
	 * @param string $errorCode - i18n code of the error message
	 */
	public function __construct( $message, $errorCode ) {
		parent::__construct( $message );

		$this->errorCode = $errorCode;
	}

	public function getErrorCode() {
		return $this->errorCode;
	}

}
