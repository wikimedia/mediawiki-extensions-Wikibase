<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * An AlternativeValidator uses a list of sub-validators to validate the data.
 * It does not implement any validation logic directly.
 * The AlternativeValidator considers the data to be valid if any of the
 * inner validators accepts the data.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class AlternativeValidator implements ValueValidator {

	/**
	 * @var ValueValidator[]
	 */
	private $validators;

	/**
	 * @param ValueValidator[] $validators
	 */
	public function __construct( array $validators ) {
		//TODO: make sure they are all instances of ValueValidator
		$this->validators = $validators;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param mixed $value The value to validate
	 *
	 * @return Result
	 */
	public function validate( $value ) {
		$result = null;

		foreach ( $this->validators as $validator ) {
			$subResult = $validator->validate( $value );

			if ( $subResult->isValid() ) {
				return $subResult;
			} else {
				$result = $result ? Result::merge( $result, $subResult ) : $subResult;
			}
		}

		if ( !$result ) {
			$result = Result::newError( [
				Error::newError(
					"No validators",
					null,
					'no-validators'
				),
			] );
		}

		return $result;
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
