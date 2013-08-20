<?php

namespace DataValues;

use InvalidArgumentException;

/**
 * Factory for DataValue objects.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValue
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class DataValueFactory {

	/**
	 * Field holding the registered data values.
	 * Data value type pointing to name of DataValue implementing class.
	 *
	 * @since 0.1
	 *
	 * @var string[]
	 */
	protected $values = array();

	/**
	 * Singleton.
	 * @deprecated Create your own instance rather then relying on global state
	 *
	 * @since 0.1
	 *
	 * @return DataValueFactory
	 */
	public static function singleton() {
		static $instance = null;

		if ( $instance === null ) {
			$instance = new DataValueFactory();
		}

		foreach ( $GLOBALS['wgDataValues'] as $type => $class ) {
			$instance->registerDataValue( $type, $class );
		}

		return $instance;
	}

	/**
	 * Registers a data value.
	 * If there is a data value already with the provided name,
	 * it will be overridden with the newly provided data.
	 *
	 * @since 0.1
	 *
	 * @param string $dataValueType
	 * @param string $class
	 *
	 * @throws InvalidArgumentException
	 */
	public function registerDataValue( $dataValueType, $class ) {
		if ( !is_string( $dataValueType ) ) {
			throw new InvalidArgumentException( 'Data value types can only be of type string' );
		}

		if ( !is_string( $class ) ) {
			throw new InvalidArgumentException( 'DataValue class names can only be of type string' );
		}

		$this->values[$dataValueType] = $class;
	}

	/**
	 * Constructs and returns a new DataValue of specified type with the provided data.
	 *
	 * @since 0.1
	 *
	 * @param string $dataValueType
	 * @param mixed  $data
	 *
	 * @return DataValue
	 * @throws IllegalValueException
	 * @throws InvalidArgumentException
	 */
	public function newDataValue( $dataValueType, $data ) {
		if ( !is_string( $dataValueType ) || $dataValueType === '' ) {
			throw new InvalidArgumentException( '$dataValueType must be a non-empty string' );
		}

		$class = $this->getDataValueClass( $dataValueType );
		$value = $class::newFromArray( $data );

		return $value;
	}

	/**
	 * Constructs and returns a new DataValue of specified type with the provided data,
	 * using an UnDeserializableValue object to represent a bad data structure.
	 *
	 * @see DataValueFactory::newDataValue returns.
	 *
	 * @since 0.1
	 *
	 * @param string $dataValueType
	 * @param mixed  $data
	 *
	 * @return DataValue
	 */
	public function tryNewDataValue( $dataValueType, $data ) {
		try {
			$value = $this->newDataValue( $dataValueType, $data );
		} catch ( IllegalValueException $ex ) {
			$value = new UnDeserializableValue( $data, $dataValueType, $ex->getMessage() );
		}

		return $value;
	}

	/**
	 * Constructs a DataValue from its array representation.
	 * This is what @see DataValue::toArray returns.
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return DataValue
	 * @throws IllegalValueException
	 */
	public function newFromArray( array $data ) {
		if ( !array_key_exists( 'type', $data ) ) {
			throw new IllegalValueException( 'DataValue type is missing' );
		}

		if ( $data['type'] === null || $data['type'] === '' ) {
			throw new IllegalValueException( 'DataValue type is empty' );
		}

		if ( !array_key_exists( 'value', $data ) ) {
			throw new IllegalValueException( 'DataValue value is missing' );
		}

		return $this->newDataValue( $data['type'], $data['value'] );
	}

	/**
	 * Constructs a DataValue from it's array representation, using an UnDeserializableValue
	 * when the given data is invalid.
	 *
	 * @see DataValueFactory::newFromArray returns.
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return DataValue
	 */
	public function tryNewFromArray( array $data ) {
		if ( !array_key_exists( 'type', $data ) ) {
			$valueData = array_key_exists( 'value', $data ) ? $data['value'] : null;
			return new UnDeserializableValue( $valueData, null, 'No type specified' );
		}

		if ( $data['type'] === null || $data['type'] === '' ) {
			$valueData = array_key_exists( 'value', $data ) ? $data['value'] : null;
			return new UnDeserializableValue( $valueData, $data['type'], 'No type specified' );
		}

		if ( !array_key_exists( 'value', $data ) ) {
			return new UnDeserializableValue( null, $data['type'], 'No value data' );
		}

		try {
			$value = $this->newFromArray( $data );
		} catch ( IllegalValueException $ex ) {
			$value = new UnDeserializableValue( $data['value'], $data['type'], $ex->getMessage() );
		}

		return $value;
	}

	/**
	 * Returns the class associated with the provided DataValue type.
	 *
	 * @since 0.1
	 *
	 * @param string $dataValueType
	 *
	 * @return string
	 * @throws IllegalValueException
	 */
	protected function getDataValueClass( $dataValueType ) {
		if ( !array_key_exists( $dataValueType, $this->values ) ) {
			throw new IllegalValueException( 'Unknown data value type "' . $dataValueType . '" has no associated DataValue class' );
		}

		return $this->values[$dataValueType];
	}

	/**
	 * Returns the types of the registered DataValues.
	 *
	 * @since 0.1
	 *
	 * @return string[]
	 */
	public function getDataValues() {
		return array_keys( $this->values );
	}

	/**
	 * Returns if there is a DataValue with the provided type.
	 *
	 * @since 0.1
	 *
	 * @param string $dataValueType DataValue type
	 *
	 * @return boolean
	 */
	public function hasDataValue( $dataValueType ) {
		return array_key_exists( $dataValueType, $this->values );
	}

}
