<?php

namespace DataValues;

/**
 * Class representing a simple numeric value.
 *
 * More complex numeric values that have associated info such as
 * unit and accuracy can be represented with a @see QuantityValue.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValue
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class NumberValue extends DataValueObject {

	/**
	 * @since 0.1
	 *
	 * @var int|float
	 */
	protected $value;

	/**
	 * @since 0.1
	 *
	 * @param int|float $value
	 *
	 * @throws IllegalValueException
	 */
	public function __construct( $value ) {
		if ( !is_int( $value ) && !is_float( $value ) ) {
			throw new IllegalValueException( 'Can only construct NumberValue from floats or integers' );
		}

		$this->value = $value;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return int|float
	 */
	public function serialize() {
		return serialize( $this->value );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @return NumberValue
	 */
	public function unserialize( $value ) {
		$this->__construct( unserialize( $value ) );
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public static function getType() {
		return 'number';
	}

	/**
	 * @see DataValue::getSortKey
	 *
	 * @since 0.1
	 *
	 * @return string|float|int
	 */
	public function getSortKey() {
		return $this->value;
	}

	/**
	 * Returns the number.
	 * @see DataValue::getValue
	 *
	 * @since 0.1
	 *
	 * @return int|float
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Constructs a new instance of the DataValue from the provided data.
	 * This can round-trip with @see getArrayValue
	 *
	 * @since 0.1
	 *
	 * @param mixed $data
	 *
	 * @return NumberValue
	 */
	public static function newFromArray( $data ) {
		return new static( $data );
	}

}
