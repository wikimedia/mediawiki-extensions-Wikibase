<?php

namespace Wikibase;

/**
 * Exception with a string error code.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ExceptionWithCode extends \Exception {

	/**
	 * @var string
	 */
	private $stringCode;

	/**
	 * @since 0.4
	 *
	 * @param string $message
	 * @param string $code
	 */
	public function __construct( $message, $code ) {
		parent::__construct( $message );
		$this->stringCode = $code;
	}

	/**
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getErrorCode() {
		return $this->stringCode;
	}

}
