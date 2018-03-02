<?php

namespace Wikibase\Repo;

use OutOfBoundsException;
use ValueValidators\ValueValidator;
use Wikimedia\Assert\Assert;

/**
 * A factory providing ValueValidators based on factory callbacks.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class BuilderBasedDataTypeValidatorFactory implements DataTypeValidatorFactory {

	/**
	 * @var callable[]
	 */
	private $validatorBuilders;

	/**
	 * @param callable[] $validatorBuilders
	 */
	public function __construct( array $validatorBuilders ) {
		Assert::parameterElementType( 'callable', $validatorBuilders, '$validatorBuilders' );

		$this->validatorBuilders = $validatorBuilders;
	}

	/**
	 * @param string $dataTypeId
	 *
	 * @throws OutOfBoundsException
	 * @return ValueValidator[]
	 */
	public function getValidators( $dataTypeId ) {
		if ( !isset( $this->validatorBuilders[ $dataTypeId ] ) ) {
			// NOTE: fail hard, to avoid bypassing validators if the data type is mistyped or some such.
			throw new OutOfBoundsException( 'No validators known for data type ' . $dataTypeId );
		}

		$validators = call_user_func(
			$this->validatorBuilders[ $dataTypeId ]
		);

		Assert::postcondition(
			is_array( $validators ),
			"Factory function for $dataTypeId did not return an array of ValueValidator objects."
		);

		foreach ( $validators as $v ) {
			Assert::postcondition(
				$v instanceof ValueValidator,
				"Factory function for $dataTypeId did not return an array of ValueValidator objects."
			);
		}

		return $validators;
	}

}
