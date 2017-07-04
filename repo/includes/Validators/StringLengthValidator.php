<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * StringLengthValidator checks a string's length
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class StringLengthValidator implements ValueValidator {

	/**
	 * @var int
	 */
	private $minLength;

	/**
	 * @var int
	 */
	private $maxLength;

	/**
	 * @var callable
	 */
	private $measure;

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
	 */
	public function validate( $value ) {
		$length = call_user_func( $this->measure, $value );

		if ( $length < $this->minLength ) {
			// XXX: having to provide an array is quite inconvenient
			return Result::newError( [
				Error::newError(
					'Too short, minimum length is ' . $this->minLength,
					null,
					'too-short',
					[ $this->minLength, $value ]
				),
			] );
		}

		if ( $length > $this->maxLength ) {
			return Result::newError( [
				Error::newError(
					'Too long, maximum length is ' . $this->maxLength,
					null,
					'too-long',
					[ $this->maxLength, $this->truncateValue( $value ) ]
				),
			] );
		}

		return Result::newSuccess();
	}

	/**
	 * @see ValueValidator::setOptions()
	 *
	 * @param array $options
	 *
	 * @codeCoverageIgnore
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}

	/**
	 * Truncates the value to the max length, if the value is larger than some maximum length.
	 * To be used only for informative purposes (such as error messages) when the value is
	 * already known to be longer than the maximum specified in the constructor.
	 *
	 * @param string $value
	 * @param int $truncateAt The length after which to truncate.
	 * Note that this is unrelated to the max length we validate against.
	 *
	 * @return string
	 */
	private function truncateValue( $value, $truncateAt = 32 ) {
		$length = call_user_func( $this->measure, $value );

		if ( $length > $truncateAt ) {
			$value = substr( $value, 0, max( 1, $truncateAt - 3 ) ) . '...';
		}

		return $value;
	}

}
