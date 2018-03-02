<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * NumberRangeValidator checks that a numerical value is in a given range.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class NumberRangeValidator implements ValueValidator {

	/**
	 * @var int|float
	 */
	private $min;

	/**
	 * @var int|float
	 */
	private $max;

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
	 * @param int|float $value The numeric value to validate
	 *
	 * @return Result
	 */
	public function validate( $value ) {
		if ( $value < $this->min ) {
			// XXX: having to provide an array is quite inconvenient
			return Result::newError( [
				Error::newError(
					'Value out of range, the minimum value is ' . $this->min,
					null,
					'too-low',
					[ $this->min, $value ]
				),
			] );
		}

		if ( $value > $this->max ) {
			return Result::newError( [
				Error::newError(
					'Value out of range, the maximum value is ' . $this->max,
					null,
					'too-high',
					[ $this->max, $value ]
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

}
