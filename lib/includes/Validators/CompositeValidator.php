<?php

namespace Wikibase\Validators;

use InvalidArgumentException;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * A CompositeValidator uses a list of sub-validators to validate the data.
 * It does not implement any validation logic directly.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class CompositeValidator implements ValueValidator {

	/**
	 * @var ValueValidator[]
	 */
	protected $validators;

	/**
	 * @var bool
	 */
	protected $failFast;

	/**
	 * @param ValueValidator[] $validators
	 * @param bool $failFast If true, validation will be aborted after the first sub validator fails.
	 */
	public function __construct( array $validators, $failFast = true ) {
		//TODO: make sure they are all instances of ValueValidator
		$this->validators = $validators;
		$this->failFast = $failFast;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param mixed $value The value to validate
	 *
	 * @return Result
	 * @throws InvalidArgumentException
	 */
	public function validate( $value ) {
		$result = Result::newSuccess();

		foreach ( $this->validators as $validator ) {
			$subResult = $validator->validate( $value );

			if ( !$subResult->isValid() ) {
				if ( $this->failFast ) {
					return $subResult;
				} else {
					$result = Result::merge( $result, $subResult );
				}
			}
		}

		return $result;
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