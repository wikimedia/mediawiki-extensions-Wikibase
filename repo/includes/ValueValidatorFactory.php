<?php

namespace Wikibase\Repo;

use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use ValueValidators\ValueValidator;

/**
 * Builds ValueValidator objects
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ValueValidatorFactory {

	/**
	 * Maps validator id to ValueValidator class or builder callback.
	 *
	 * @since 0.5
	 *
	 * @var array
	 */
	protected $validators = array();

	/**
	 * @since 0.5
	 *
	 * @param string|callable[] $valueValidators An associative array mapping validator ids to
	 *        class names or callable builders.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $valueValidators ) {
		foreach ( $valueValidators as $validatorId => $validatorBuilder ) {
			if ( !is_string( $validatorId ) ) {
				throw new InvalidArgumentException( 'Validator id needs to be a string' );
			}

			if ( !is_string( $validatorBuilder ) && !is_callable( $validatorBuilder ) ) {
				throw new InvalidArgumentException( 'Validator class needs to be a class name or callable' );
			}

			$this->validators[$validatorId] = $validatorBuilder;
		}
	}

	/**
	 * Returns the ValueValidator identifiers.
	 *
	 * @since 0.5
	 *
	 * @return string[]
	 */
	public function getValidatorIds() {
		return array_keys( $this->validators );
	}

	/**
	 * Returns the validator builder (class name or callable) for $validatorId, or null if
	 * no builder was registered for that id.
	 *
	 * @since 0.5
	 *
	 * @param string $validatorId
	 *
	 * @return string|callable|null
	 */
	public function getValidatorBuilder( $validatorId ) {
		if ( array_key_exists( $validatorId, $this->validators ) ) {
			return $this->validators[$validatorId];
		}

		return null;
	}

	/**
	 * Returns an instance of the ValueValidator with the provided id or null if there is no such ValueValidator.
	 *
	 * @since 0.5
	 *
	 * @param string $validatorId
	 *
	 * @throws OutOfBoundsException If no validator was registered for $validatorId
	 * @return ValueValidator
	 */
	public function newValidator( $validatorId ) {
		if ( !array_key_exists( $validatorId, $this->validators ) ) {
			throw new OutOfBoundsException( "No builder registered for validator ID $validatorId" );
		}

		$builder = $this->validators[$validatorId];
		$validator = $this->instantiateValidator( $builder );

		return $validator;
	}

	/**
	 * @param string|callable $builder Either a classname of an implementation of ValueValidator,
	 *        or a callable that returns a ValueValidator.
	 *
	 * @throws LogicException if the builder did not create a ValueValidator
	 * @return ValueValidator
	 */
	private function instantiateValidator( $builder ) {
		if ( is_string( $builder ) ) {
			$validator = new $builder();
		} else {
			$validator = call_user_func( $builder );
		}

		if ( !( $validator instanceof ValueValidator ) ) {
			throw new LogicException(
				'Invalid validator builder, did not create an instance of ValueValidator.'
			);
		}

		return $validator;
	}

}
