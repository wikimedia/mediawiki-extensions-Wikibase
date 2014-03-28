<?php

namespace Wikibase\ChangeOp;

use Exception;
use InvalidArgumentException;
use ValueValidators\Result;

/**
 * Exception thrown when the validation of a change operation failed failed.
 * This is essentially a wrapper for ValueValidators\Result.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ChangeOpValidationException extends ChangeOpException {

	/**
	 * @var Result
	 */
	protected $result;

	/**
	 * @param Result $result
	 * @param Exception $previous
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( Result $result, Exception $previous = null ) {
		if ( $result->isValid() ) {
			throw new InvalidArgumentException( 'Can\'t construct an exception from a valid result!' );
		}

		parent::__construct( 'validation failed', 0, $previous );

		$this->result = $result;
	}

	/**
	 * @return Result
	 */
	public function getValidationResult() {
		return $this->result;
	}

}
