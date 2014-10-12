<?php

namespace Wikibase\Validators;

use DataTypes\DataTypeFactory;
use DataValues\DataValue;
use DataValues\UnDeserializableValue;
use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\Entity\PropertyNotFoundException;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;

/**
 * Class SnakValidator for validating Snaks.
 *
 * @since 0.4
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SnakValidator implements ValueValidator {

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	public function __construct(
		PropertyDataTypeLookup $propertyDataTypeLookup,
		DataTypeFactory $dataTypeFactory
	) {
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->dataTypeFactory = $dataTypeFactory;
	}

	/**
	 * Applies validation to the given Claim.
	 * This is done by validating all snaks contained in the claim, notably:
	 * the main snak, the qualifiers, and all snaks of all references,
	 * in case the claim is a Statement.
	 *
	 * @param Claim $claim The value to validate
	 *
	 * @return Result
	 */
	public function validateClaimSnaks( Claim $claim ) {
		$snak = $claim->getMainSnak();
		$result = $this->validate( $snak );

		if ( !$result->isValid() ) {
			return $result;
		}

		foreach ( $claim->getQualifiers() as $snak ) {
			$result = $this->validate( $snak );

			if ( !$result->isValid() ) {
				return $result;
			}
		}

		if ( $claim instanceof Statement ) {
			$result = $this->validateReferences( $claim->getReferences() );

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
		} catch ( PropertyNotFoundException $ex ) {
			$result = Result::newError( array(
				Error::newError( "Property $propertyId not found!", null, 'no-such-property', array( $propertyId ) )
			) );
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
		$dataType = $this->dataTypeFactory->getType( $dataTypeId );

		if ( $dataValue instanceof UnDeserializableValue ) {
			$result = Result::newError( array(
				Error::newError(
					'Bad snak value: ' . $dataValue->getReason(),
					null,
					'bad-value',
					array( $dataValue->getReason() )
				),
			) );
		} elseif ( $dataType->getDataValueType() != $dataValue->getType() ) {
			$result = Result::newError( array(
				Error::newError(
					'Bad value type: ' . $dataValue->getType() . ', expected ' . $dataType->getDataValueType(),
					null,
					'bad-value-type',
					array( $dataValue->getType(), $dataType->getDataValueType() )
				),
			) );
		} else {
			$result = Result::newSuccess();
		}

		//XXX: Perhaps DataType should have a validate() method (even implement ValueValidator)
		//     At least, DataType should expose only one validator, which would be a CompositeValidator
		foreach ( $dataType->getValidators() as $validator ) {
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
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}

}
