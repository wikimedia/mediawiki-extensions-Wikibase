<?php

namespace Wikibase\Repo;

/**
 * A factory providing ValueValidators based on DataType id that uses ValidatorBuilders.
 *
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class BuilderBasedDataTypeValidatorFactory implements DataTypeValidatorFactory {

	/**
	 * @var callable[]
	 */
	private $validatorBuilders;

	/**
	 * @param ValidatorBuilders $validatorBuilders
	 */
	public function __construct( ValidatorBuilders $validatorBuilders ) {
		$this->validatorBuilders = $validatorBuilders->getDataTypeValidators();
	}

	/**
	 *
	 * @param string $dataTypeId
	 *
	 * @return ValueValidator[]
	 */
	public function getValidators( $dataTypeId ) {
		return call_user_func(
			$this->validatorBuilders[ $dataTypeId ]
		);
	}

}
