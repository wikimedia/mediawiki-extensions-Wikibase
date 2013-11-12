<?php

/**
 * Should be moved to core!
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class MessageException extends Exception {

	protected $key;

	protected $params;

	public function __construct( $key, array $params, $message, Exception $previous = null ) {
		parent::__construct( $message, 0, $previous );

		$this->key = $key;
		$this->params = $params;
	}

	public function getKey() {
		return $this->key;
	}

	public function getParams() {
		return $this->params;
	}

}
