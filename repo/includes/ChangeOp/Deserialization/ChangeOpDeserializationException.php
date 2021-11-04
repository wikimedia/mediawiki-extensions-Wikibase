<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

/**
 * Thrown from ChangeOpDeserializers to be handled by a higher abstraction layer such as the API
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpDeserializationException extends \InvalidArgumentException {

	/**
	 * @var string
	 */
	private $errorCode;

	/**
	 * @var array
	 */
	private $params;

	/**
	 * @param string $message descriptive error message to be used in logs
	 * @param string $errorCode i18n code of the error message
	 * @param array $params optional parameters (e.g. i18n message arguments)
	 */
	public function __construct( $message, $errorCode, array $params = [] ) {
		parent::__construct( $message );

		$this->errorCode = $errorCode;
		$this->params = $params;
	}

	public function getErrorCode() {
		return $this->errorCode;
	}

	public function getParams() {
		return $this->params;
	}

}
