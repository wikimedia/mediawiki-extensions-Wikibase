<?php

namespace Wikibase\Validators;

use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * StringLengthValidator checks a string's length
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class StringLengthValidator implements ValueValidator {

	/**
	 * @var int
	 */
	protected $minLength;

	/**
	 * @var int
	 */
	protected $maxLength;

	/**
	 * @var callable
	 */
	protected $measure;

	/**
	 * @param int             $minLength
	 * @param int             $maxLength
	 * @param callable|string $measure The function to use to measure the string's length.
	 *                        Use 'strlen' for byte length and 'mb_strlen' for character length.
	 *                        A callable can be used to provide an alternative measure.
	 */
	public function __construct( $minLength, $maxLength, $measure = 'strlen' ) {
		//TODO: check type
		$this->minLength = $minLength;
		$this->maxLength = $maxLength;

		//TODO: check callable
		$this->measure = $measure;
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
		$length = call_user_func( $this->measure, $value );

		if ( $length < $this->minLength ) {
			// XXX: having to provide an array is quite inconvenient
			return Result::newError( array(
				Error::newError( 'Too short, minimum length is ' . $this->minLength, null, 'too-short', array( $this->minLength, $value ) )
			) );
		}

		if ( $length > $this->maxLength ) {
			return Result::newError( array(
				Error::newError( 'Too long, maximum length is ' . $this->maxLength, null, 'too-long', array( $this->maxLength, $value ) )
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