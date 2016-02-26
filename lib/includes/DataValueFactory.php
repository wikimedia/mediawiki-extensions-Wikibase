<?php

namespace DataValues;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use InvalidArgumentException;

/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class DataValueFactory {

	private $deserializer;

	public function __construct( Deserializer $dataValueDeserializer ) {
		$this->deserializer = $dataValueDeserializer;
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
	 * @throws InvalidArgumentException
	 */
	public function newDataValue( $dataValueType, $data ) {
		if ( !is_string( $dataValueType ) || $dataValueType === '' ) {
			throw new InvalidArgumentException( '$dataValueType must be a non-empty string' );
		}

		try {
			$value = $this->deserializer->deserialize( array(
				'value' => $data,
				'type' => $dataValueType
			) );
		} catch ( DeserializationException $ex ) {
			throw new InvalidArgumentException( $ex->getMessage(), 0, $ex );
		}

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

}
