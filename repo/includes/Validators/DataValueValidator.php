<?php

namespace Wikibase\Repo\Validators;

use DataValues\DataValue;
use InvalidArgumentException;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * The DataValueValidator class allows the validation of the plain value
 * of a simple DataValues object based on the DataValue's array representation.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DataValueValidator implements ValueValidator {

	/**
	 * @var ValueValidator
	 */
	private $validator;

	/**
	 * @param ValueValidator $validator The validator to apply to the given field
	 */
	public function __construct( ValueValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param DataValue $value The value to validate
	 *
	 * @throws InvalidArgumentException
	 * @return Result
	 */
	public function validate( $value ) {
		if ( !( $value instanceof DataValue ) ) {
			throw new InvalidArgumentException( 'DataValue expected' );
		}

		$arrayValue = $value->getArrayValue();
		$result = $this->validator->validate( $arrayValue );
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
