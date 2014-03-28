<?php

namespace Wikibase\ChangeOp;

use Exception;
use InvalidArgumentException;
use ValueValidators\Result;
use ValueValidators\Error;

/**
 * Exception thrown when the validation of a change operation failed.
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

		$messages = $this->composeErrorMessage( $result->getErrors() );
		parent::__construct( 'validation failed: ' . $messages, 0, $previous );

		$this->result = $result;
	}

	/**
	 * @return Result
	 */
	public function getValidationResult() {
		return $this->result;
	}

	/**
	 * @param Error[] $errors
	 */
	private function composeErrorMessage( $errors ) {
		$text = '';

		foreach ( $errors as $error ) {
			$text .= $error->getText();
			$text .= '; ';
		}

		return $text;
	}
}
