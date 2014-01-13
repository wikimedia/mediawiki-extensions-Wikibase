<?php

namespace Wikibase\Test;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\DataValue;
use DataValues\StringValue;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\StringValidator;
use ValueValidators\ValueValidator;
use Wikibase\Claim;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Property;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Reference;
use Wikibase\ReferenceList;
use Wikibase\References;
use Wikibase\Snak;
use Wikibase\SnakList;
use Wikibase\Statement;
use Wikibase\Validators\DataValueValidator;
use Wikibase\Validators\SnakValidator;

/**
 * A simple validator for use in unit tests.
 * Checks a string value against a regular expression.
 * If the value is a DataValue object, it's native representation
 * ("array value") will be checked.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TestValidator implements ValueValidator {
	protected $regex;

	public function __construct( $regex ) {
		$this->regex = $regex;
	}

	/**
	 * @return Result
	 */
	public function validate( $value ) {
		if ( $value instanceof DataValue ) {
			$value = $value->getArrayValue();
		}

		if ( preg_match( $this->regex, $value ) ) {
			return Result::newSuccess();
		} else {
			return Result::newError( array(
				Error::newError( "doesn't match " . $this->regex )
			) );
		}
	}

	/**
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		// noop
	}
}
