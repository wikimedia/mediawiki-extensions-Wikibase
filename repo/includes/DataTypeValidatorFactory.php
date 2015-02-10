<?php

namespace Wikibase\Repo;

/**
 * Description of DataTypeValidatorFactory
 *
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class DataTypeValidatorFactory {

	/**
	 * @var callable[]
	 */
	private $validatorBuilders;

	//put your code here

	public function __construct( ValidatorBuilders $validatorBuilders ) {
		$this->validatorBuilders = $validatorBuilders->getDataTypeValidators();
	}

	/**
	 * 
	 * @param string $dataValueType
	 * 
	 * @return ValueValidator[]
	 */
	public function getValidators( $dataValueType ) {
		return call_user_func(
			$this->validatorBuilders[ $dataValueType ]
		);
	}

}
