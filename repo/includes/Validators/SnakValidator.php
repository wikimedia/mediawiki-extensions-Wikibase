<?php

namespace Wikibase\Repo\Validators;

use DataValues\DataValue;
use DataValues\UnDeserializableValue;
use InvalidArgumentException;
use OutOfBoundsException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Repo\DataTypeValidatorFactory;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SnakValidator implements ValueValidator {

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var DataTypeValidatorFactory
	 */
	private $validatorFactory;

	public function __construct(
		PropertyDataTypeLookup $propertyDataTypeLookup,
		DataTypeFactory $dataTypeFactory,
		DataTypeValidatorFactory $validatorFactory
	) {
		$this->dataTypeFactory = $dataTypeFactory;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->validatorFactory = $validatorFactory;
	}

	/**
	 * Validates the Statement's main snak, qualifiers, and references.
	 */
	public function validateStatementSnaks( Statement $statement ): Result {
		$snak = $statement->getMainSnak();
		$result = $this->validate( $snak );

		if ( !$result->isValid() ) {
			return $result;
		}

		foreach ( $statement->getQualifiers() as $snak ) {
			$result = $this->validate( $snak );

			if ( !$result->isValid() ) {
				return $result;
			}
		}

		$result = $this->validateReferences( $statement->getReferences() );

		if ( !$result->isValid() ) {
			return $result;
		}

		return Result::newSuccess();
	}

	/**
	 * Validate a list of references.
	 * This is done by validating all snaks in all of the references.
	 *
	 * @param ReferenceList $references
	 *
	 * @return Result
	 */
	public function validateReferences( ReferenceList $references ) {
		foreach ( $references as $reference ) {
			$result = $this->validateReference( $reference );

			if ( !$result->isValid() ) {
				return $result;
			}
		}

		return Result::newSuccess();
	}

	/**
	 * Validate a list of references.
	 * This is done by validating all snaks in all of the references.
	 *
	 * @param Reference $reference
	 *
	 * @return Result
	 */
	public function validateReference( Reference $reference ) {
		foreach ( $reference->getSnaks() as $snak ) {
			$result = $this->validate( $snak );

			if ( !$result->isValid() ) {
				return $result;
			}
		}

		return Result::newSuccess();
	}

	/**
	 * Validates a Snak.
	 * For a PropertyValueSnak, this is done using the validators from the DataType
	 * that is associated with the Snak's property.
	 * Other Snak types are currently not validated.
	 *
	 * @see ValueValidator::validate()
	 *
	 * @param Snak $snak The value to validate
	 *
	 * @throws InvalidArgumentException
	 * @return Result
	 */
	public function validate( $snak ) {
		if ( !( $snak instanceof Snak ) ) {
			throw new InvalidArgumentException( 'Snak expected' );
		}

		// XXX: instead of an instanceof check, we could have multiple validators
		//      with a canValidate() method, to determine which validator to use
		//      for a given snak.

		$propertyId = $snak->getPropertyId();

		try {
			$typeId = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId );

			if ( $snak instanceof PropertyValueSnak ) {
				$dataValue = $snak->getDataValue();
				$result = $this->validateDataValue( $dataValue, $typeId );
			} else {
				$result = Result::newSuccess();
			}
		} catch ( PropertyDataTypeLookupException $ex ) {
			$result = Result::newError( [
				Error::newError( "Property $propertyId not found!", null, 'no-such-property', [ $propertyId ] ),
			] );
		}

		return $result;
	}

	/**
	 * Validates the given data value using the given data type.
	 *
	 * @param DataValue $dataValue
	 * @param string    $dataTypeId
	 *
	 * @return Result
	 */
	public function validateDataValue( DataValue $dataValue, $dataTypeId ) {
		try {
			$dataValueType = $this->dataTypeFactory->getType( $dataTypeId )->getDataValueType();
		} catch ( OutOfBoundsException $ex ) {
			return Result::newError( [
				Error::newError(
					'Bad data type: ' . $dataTypeId,
					null,
					'bad-data-type',
					[ $dataTypeId ]
				),
			] );
		}

		if ( $dataValue instanceof UnDeserializableValue ) {
			return Result::newError( [
				Error::newError(
					'Bad snak value: ' . $dataValue->getReason(),
					null,
					'bad-value',
					[ $dataValue->getReason() ]
				),
			] );
		} elseif ( $dataValueType != $dataValue->getType() ) {
			return Result::newError( [
				Error::newError(
					'Bad value type: ' . $dataValue->getType() . ', expected ' . $dataValueType,
					null,
					'bad-value-type',
					[ $dataValue->getType(), $dataValueType ]
				),
			] );
		}

		$result = Result::newSuccess();

		//XXX: DataTypeValidatorFactory should expose only one validator, which would be a CompositeValidator
		foreach ( $this->validatorFactory->getValidators( $dataTypeId ) as $validator ) {
			$subResult = $validator->validate( $dataValue );

			//XXX: Some validators should be fatal and cause us to abort the loop.
			//     Others shouldn't.

			if ( !$subResult->isValid() ) {
				//TODO: Don't bail out immediately. Accumulate errors from all validators.
				//      We need Result::merge() for this.
				return $subResult;
			}
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
