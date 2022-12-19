<?php

namespace Wikibase\Repo\Validators;

use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * MembershipValidator checks that a value is in a fixed set of allowed values.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MembershipValidator implements ValueValidator {

	/**
	 * @var string[]
	 */
	private $allowed;

	/**
	 * @var string
	 */
	private $errorCode;

	/**
	 * @var callable|string|null
	 */
	private $normalizer;

	/**
	 * @param string[] $allowed The allowed values
	 * @param string $errorCode Code to use in Errors; should indicate what kind of value would have been allowed.
	 * @param callable|string|null $normalizer An optional function to normalize the value before
	 *                        comparing it to the list of allowed values, e.g. 'strtolower'.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $allowed, $errorCode = 'not-allowed', $normalizer = null ) {
		if ( !is_string( $errorCode ) ) {
			throw new InvalidArgumentException( 'Error code must be a string' );
		}

		if ( !is_callable( $normalizer ) && $normalizer !== null ) {
			throw new InvalidArgumentException( 'Normalizer must be callable (or null)' );
		}

		$this->allowed = $allowed;
		$this->errorCode = $errorCode;
		$this->normalizer = $normalizer;
	}

	/**
	 * @see ValueValidator::validate
	 *
	 * @param string $value The value to validate
	 *
	 * @return Result
	 */
	public function validate( $value ) {
		if ( $this->normalizer !== null ) {
			$value = call_user_func( $this->normalizer, $value );
		}

		if ( !in_array( $value, $this->allowed, true ) ) {
			return Result::newError( [
				Error::newError(
					'Not a legal value: ' . $value,
					null,
					$this->errorCode,
					[ $value ]
				),
			] );
		}

		return Result::newSuccess();
	}

	/**
	 * @see ValueValidator::setOptions
	 *
	 * @param array $options
	 *
	 * @codeCoverageIgnore
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}

}
