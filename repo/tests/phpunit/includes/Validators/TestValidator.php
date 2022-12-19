<?php

namespace Wikibase\Repo\Tests\Validators;

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
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class TestValidator implements ValueValidator {

	/**
	 * @var string
	 */
	protected $regex;

	/**
	 * @param string $regex
	 */
	public function __construct( $regex ) {
		$this->regex = $regex;
	}

	/**
	 * @param string|DataValue $value
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
			return Result::newError( [
				Error::newError( "doesn't match " . $this->regex ),
			] );
		}
	}

	/**
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		// noop
	}

}
