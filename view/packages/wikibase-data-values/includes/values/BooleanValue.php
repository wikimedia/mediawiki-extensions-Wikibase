<?php

namespace DataValues;

/**
 * Class representing a boolean value.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValue
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class BooleanValue extends DataValueObject {

	/**
	 * @since 0.1
	 *
	 * @var boolean
	 */
	protected $value;

	/**
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @throws IllegalValueException
	 */
	public function __construct( $value ) {
		if ( !is_bool( $value ) ) {
			throw new IllegalValueException( 'Can only construct BooleanValue from booleans' );
		}

		$this->value = $value;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return $this->value ? '1' : '0';
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @return BooleanValue
	 */
	public function unserialize( $value ) {
		$this->__construct( $value === '1' );
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public static function getType() {
		return 'boolean';
	}

	/**
	 * @see DataValue::getSortKey
	 *
	 * @since 0.1
	 *
	 * @return string|float|int
	 */
	public function getSortKey() {
		return $this->value ? 1 : 0;
	}

	/**
	 * Returns the boolean.
	 * @see DataValue::getValue
	 *
	 * @since 0.1
	 *
	 * @return boolean
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
	 * @return BooleanValue
	 */
	public static function newFromArray( $data ) {
		return new static( $data );
	}

}
