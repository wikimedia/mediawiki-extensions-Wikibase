<?php

namespace Wikibase\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * RegexValidator checks a string against a regular expression.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class RegexValidator implements ValueValidator {

	/**
	 * @var string
	 */
	private $expression;

	/**
	 * @var bool
	 */
	private $inverse;

	/**
	 * @var string
	 */
	private $errorCode;

	/**
	 * @param string  $expression
	 * @param bool    $inverse
	 * @param string  $errorCode code to use when this validator fails.
	 */
	public function __construct( $expression, $inverse = false, $errorCode = 'malformed-value' ) {
		//TODO: check type
		$this->expression = $expression;
		$this->inverse = $inverse;
		$this->errorCode = $errorCode;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param string $value The value to validate
	 *
	 * @return Result
	 */
	public function validate( $value ) {
		$match = preg_match( $this->expression, $value );

		if ( $match === 0 && !$this->inverse ) {
			// XXX: having to provide an array is quite inconvenient
			return Result::newError( array(
				Error::newError(
					'Pattern match failed: ' . $this->expression,
					null,
					$this->errorCode,
					array( $value )
				),
			) );
		}

		if ( $match === 1 && $this->inverse ) {
			// XXX: having to provide an array is quite inconvenient
			return Result::newError( array(
				Error::newError(
					'Negative pattern matched: ' . $this->expression,
					null,
					$this->errorCode,
					array( $value )
				),
			) );
		}

		return Result::newSuccess();
	}

	/**
	 * @see ValueValidator::setOptions()
	 *
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}

}
