<?php

namespace Wikibase\Validators;

use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * MembershipValidator checks that a value is in a fixed set of allowed values.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class MembershipValidator implements ValueValidator {

	/**
	 * @var array
	 */
	protected $allowedValues;

	/**
	 * @var string
	 */
	protected $code;

	/**
	 * @var callable
	 */
	protected $normalize;

	/**
	 * @param array $allowed The allowed values
	 * @param string $code Code to use in Errors; should indicate what kind of value would have been allowed.
	 * @param callable|string $normalize The function to use normalize the value before
	 *                        comparing it to the list of allowed values, e.g. 'strtolower'.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $allowed, $code = 'not-allowed', $normalize = null ) {
		if ( !is_array( $allowed ) ) {
			throw new InvalidArgumentException( '$allowed must be an array' );
		}

		if ( !is_string( $code ) ) {
			throw new InvalidArgumentException( '$code must be a string' );
		}

		if ( !is_null( $normalize ) && !is_callable( $normalize ) ) {
			throw new InvalidArgumentException( '$normalize must be callable (or null)' );
		}

		$this->allowed = $allowed;
		$this->code = $code;
		$this->normalize = $normalize;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param string $value The value to validate
	 *
	 * @return Result
	 * @throws InvalidArgumentException
	 */
	public function validate( $value ) {
		if ( $this->normalize !== null ) {
			$value = call_user_func( $this->normalize, $value );
		}

		if ( !in_array( $value, $this->allowed ) ) {
			return Result::newError( array(
				Error::newError( 'not a legal value: ' . $value, null, $this->code, array( $value ) )
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