<?php

namespace Wikibase\Lib;

use Exception;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class MessageException extends Exception {

	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var array
	 */
	protected $params;

	/**
	 * @param string $key
	 * @param array $params
	 * @param string $message
	 * @param Exception|null $previous
	 */
	public function __construct( $key, array $params, $message, Exception $previous = null ) {
		parent::__construct( $message, 0, $previous );

		$this->key = $key;
		$this->params = $params;
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @return array
	 */
	public function getParams() {
		return $this->params;
	}

}
