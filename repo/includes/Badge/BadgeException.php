<?php

namespace Wikibase\Badge;

use Exception;
use RuntimeException;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class BadgeException extends RuntimeException {

	/**
	 * @var string
	 */
	protected $messageKey;

	/**
	 * @var string
	 */
	protected $rawInput;

	/**
	 * @param string $messageKey
	 * @param string $rawInput
	 * @param string $message
	 * @param Exception $previous
	 */
	public function __construct( $messageKey, $rawInput, $message, Exception $previous = null ) {
		parent::__construct( $message, 0, $previous );

		$this->messageKey = $messageKey;
		$this->rawInput = $rawInput;
	}

	/**
	 * @param string
	 */
	public function getMessageKey() {
		return $this->messageKey;
	}

	/**
	 * @return string
	 */
	public function getRawInput() {
		return $this->rawInput;
	}

}
