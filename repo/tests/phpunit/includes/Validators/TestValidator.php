<?php

namespace Wikibase\Test;

use DataValues\DataValue;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * A simple validator for use in unit tests.
 * Checks a string value against a regular expression.
 * If the value is a DataValue object, it's native representation
 * ("array value") will be checked.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TestValidator implements ValueValidator {
	protected $regex;

	public function __construct( $regex ) {
		$this->regex = $regex;
	}

	/**
	 * @param mixed $value
	 *
	 * @return Result
	 */
	public function validate( $value ) {
		if ( $value instanceof DataValue ) {
			$value = $value->getArrayValue();
		}

		if ( preg_match( $this->regex, $value ) ) {
			return Result::newSuccess();
		} else {
			return Result::newError( array(
				Error::newError( "doesn't match " . $this->regex )
			) );
		}
	}

	/**
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		// noop
	}
}
