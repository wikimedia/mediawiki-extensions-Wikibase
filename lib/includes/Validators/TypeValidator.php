<?php

namespace Wikibase\Validators;

use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * TypeValidator checks a value's data type.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class TypeValidator implements ValueValidator {

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * Constructs a TypeValidator that checks for the given type.
	 *
	 * @param string $type A PHP type name or a class name.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $type ) {
		$this->type = $type;
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
		$type = gettype( $value );

		if ( $type === $this->type ) {
			return Result::newSuccess();
		}

		if ( is_object( $value ) ) {
			$type = get_class( $value );

			if ( is_a( $value, $this->type ) ) {
				return Result::newSuccess();
			}
		}

		return Result::newError( array(
			Error::newError( 'Bad type, expected ' . $this->type, null, 'bad-type', array( $this->type, $type ) )
		) );
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