<?php

namespace Wikibase\Repo\ChangeOp;

use Exception;
use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;

/**
 * Exception thrown when the validation of a change operation failed.
 * This is essentially a wrapper for ValueValidators\Result.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChangeOpValidationException extends ChangeOpException {

	/**
	 * @var Result
	 */
	private $result;

	/**
	 * @param Result $result
	 * @param Exception|null $previous
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( Result $result, Exception $previous = null ) {
		$messages = $this->composeErrorMessage( $result->getErrors() );
		parent::__construct( 'Validation failed: ' . $messages, 0, $previous );

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
	 *
	 * @return string
	 */
	private function composeErrorMessage( array $errors ) {
		$text = implode( '; ', array_map( function( Error $error ) {
			return $error->getText();
		}, $errors ) );

		return $text;
	}

}
