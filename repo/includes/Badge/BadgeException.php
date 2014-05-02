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
	protected $parseInput;

	/**
	 * @param string $messageKey
	 * @param string $parseInput
	 * @param string $message
	 * @param Exception $previous
	 */
	public function __construct( $messageKey, $parseInput, $message, Exception $previous = null ) {
		parent::__construct( $message, 0, $previous );

		$this->messageKey = $messageKey;
		$this->parseInput = $parseInput;
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
	public function getParseInput() {
		return $this->parseInput;
	}

}
