<?php

namespace Wikibase\ChangeOp;

use Exception;
use RuntimeException;

/**
 * Exception thrown during an invalid change operation.
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpException extends RuntimeException {

	/**
	 * @var string
	 */
	private $messageKey;

	/**
	 * @var array
	 */
	private $messageArgs;

	/**
	 * @param string $description descriptive error message (in English) to be used in logs
	 * @param string $messageKey optional key of i18n message relevant to the exception
	 * @param array $messageArgs optional arguments of the i18n message
	 * @param Exception|null $previous
	 */
	public function __construct( $description = '', $messageKey = '', array $messageArgs = [], Exception $previous = null ) {
		parent::__construct( $description, 0, $previous );

		$this->messageKey = $messageKey;
		$this->messageArgs = $messageArgs;
	}

	public function getMessageKey() {
		return $this->messageKey;
	}

	public function getMessageArgs() {
		return $this->messageArgs;
	}

}
