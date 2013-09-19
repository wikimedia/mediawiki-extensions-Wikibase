<?php

namespace Wikibase\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * NumberRangeValidator checks that a numerical value is in a given range.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class NumberRangeValidator implements ValueValidator {

	/**
	 * @var int
	 */
	protected $min;

	/**
	 * @var int
	 */
	protected $max;

	/**
	 * @var callable
	 */
	protected $measure;

	/**
	 * @param int|float  $min
	 * @param int|float  $max
	 */
	public function __construct( $min, $max ) {
		$this->min = $min;
		$this->max = $max;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param string $value The value to validate
	 *
	 * @return \ValueValidators\Result
	 * @throws \InvalidArgumentException
	 */
	public function validate( $value ) {
		if ( $value < $this->min ) {
			// XXX: having to provide an array is quite inconvenient
			return Result::newError( array(
				Error::newError( 'Value out of range, the minimum value is ' . $this->min, null, 'too-low', array( $this->min, $value ) )
			) );
		}

		if ( $value > $this->max ) {
			return Result::newError( array(
				Error::newError( 'Value out of range, the maximum value is ' . $this->max, null, 'too-high', array( $this->max, $value ) )
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