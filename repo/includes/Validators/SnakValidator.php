<?php
 /**
 *
 * Copyright © 10.06.13 by the authors listed below.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @license GPL 2+
 * @file
 *
 * @author Daniel Kinzler
 */


namespace Wikibase\Validators;


use DataTypes\DataTypeFactory;
use DataValues\DataValue;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\Claim;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Reference;
use Wikibase\References;
use Wikibase\Snak;
use Wikibase\SnakObject;
use Wikibase\Statement;

/**
 * Class SnakValidator for validating Snaks.
 *
 * @package Wikibase\Validators
 */
class SnakValidator implements ValueValidator {

	/**
	 * @var DataTypeFactory
	 */
	protected $dataTypeFactory;

	/**
	 * @var PropertyDataTypeLookup
	 */
	protected $propertyDataTypeLookup;

	public function __construct(
		PropertyDataTypeLookup $propertyDataTypeLookup,
		DataTypeFactory $dataTypeFactory ) {

		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->dataTypeFactory = $dataTypeFactory;
	}

	/**
	 * Applies validation to the given Claim.
	 * This is done by validating all snaks contained in the claim, notably:
	 * the main snak, the qualifiers, and all snaks of all references,
	 * in case the claim is a Statement.
	 *
	 * @param \Wikibase\Claim $claim The value to validate
	 *
	 * @return \ValueValidators\Result
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
	 * @param References $references
	 * @return \ValueValidators\Result
	 */
	public function validateReferences( References $references ) {
		/* @var Reference $ref */
		foreach ( $references as $ref ) {
			$result = $this->validateReference( $ref );

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
	 * @return \ValueValidators\Result
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
	 * @return \ValueValidators\Result
	 * @throws \InvalidArgumentException
	 */
	public function validate( $snak ) {
		// XXX: instead of an instanceof check, we could have multiple validators
		//      with a canValidate() method, to determine which validator to use
		//      for a given snak.

		if ( $snak instanceof SnakObject ) {

			//NOTE: We only really need the DataType in case of PropertyValueSnak,
			//      but getDataTypeIdForProperty() is a quick way to check if the property exists.

			//XXX: getDataTypeIdForProperty may throw a PropertyNotFoundException.
			//     Shall we catch that and report it using the Result object?

			$propertyId = $snak->getPropertyId();
			$typeId = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId );

			if ( $snak instanceof PropertyValueSnak ) {
				$dataValue = $snak->getDataValue();
				$result = $this->validateDataValue( $dataValue, $typeId );

				//TODO: include property ID in any error report
			} else {
				$result = Result::newSuccess();
			}
		} else {
			$result = Result::newSuccess();
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

		$result = Result::newSuccess();

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