<?php

namespace DataValue;

/**
 * Class representing a string value.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValue
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StringValue extends DataValueObject {

	/**
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $value;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 */
	public function __construct( $value ) {
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
		return $this->value;
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function unserialize( $value ) {
		$this->value = $value;
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return 'string';
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

}
