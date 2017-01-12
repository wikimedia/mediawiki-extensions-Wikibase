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
	private $messageKey;

	/**
	 * @var array
	 */
	private $messageArgs;

	/**
	 * @param string $description descriptive error message (in English) to be used in logs
	 * @param string $messageKey key of i18n message relevant to the exception
	 * @param array $messageArgs optional arguments of the translatable message
	 */
	public function __construct( $description, $messageKey, array $messageArgs = [] ) {
		parent::__construct( $description );

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
