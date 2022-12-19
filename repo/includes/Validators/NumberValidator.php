<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class NumberValidator implements ValueValidator {

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param mixed $value The value to validate
	 *
	 * @return Result
	 */
	public function validate( $value ) {
		$isValid = is_int( $value ) || is_float( $value );

		if ( $isValid ) {
			return Result::newSuccess();
		}

		return Result::newError( [
			Error::newError(
				'Bad type, expected an integer or float value',
				null,
				'bad-type',
				[ 'number', gettype( $value ) ]
			),
		] );
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
