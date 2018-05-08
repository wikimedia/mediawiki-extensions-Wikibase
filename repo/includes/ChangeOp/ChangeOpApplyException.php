<?php

namespace Wikibase\Repo\ChangeOp;

use Exception;

/**
 * Exception thrown when the validation of a change operation failed.
 * This is essentially a wrapper for ValueValidators\Result.
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpApplyException extends ChangeOpException {

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
	 * @param array $params List of parameters, depending on the message. Calling code must make
	 *  sure these parameters are properly escaped.
	 * @param Exception|null $previous
	 */
	public function __construct( $key, array $params = [], Exception $previous = null ) {
		parent::__construct( $key, 0, $previous );

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
