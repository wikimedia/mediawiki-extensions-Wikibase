<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use DataValues\DataValue;
use Wikibase\Repo\DataTypeValidatorFactory;
use Wikibase\Repo\RestApi\Validation\DataValueValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class DataTypeValidatorFactoryDataValueValidator implements DataValueValidator {

	private DataTypeValidatorFactory $validatorFactory;

	public function __construct( DataTypeValidatorFactory $validatorFactory ) {
		$this->validatorFactory = $validatorFactory;
	}

	public function validate( string $dataTypeId, DataValue $dataValue ): ?ValidationError {
		foreach ( $this->validatorFactory->getValidators( $dataTypeId ) as $validator ) {
			if ( !$validator->validate( $dataValue )->isValid() ) {
				return new ValidationError( self::CODE_INVALID_DATA_VALUE );
			}
		}

		return null;
	}

}
