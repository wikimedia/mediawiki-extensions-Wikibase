<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DataTypeFactory {

	/**
	 * Associative array mapping data type identifiers to DataType objects.
	 *
	 * @var DataType[]
	 */
	private $types = [];

	/**
	 * @var string[] Associative array mapping data type identifiers to data value type identifiers.
	 */
	private $valueTypes = [];

	/**
	 * @since 0.5
	 *
	 * @param string[] $valueTypes Associative array mapping data type identifiers (also
	 *  referred to as "property types") to data value type identifiers.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $valueTypes ) {
		foreach ( $valueTypes as $typeId => $valueType ) {
			if ( !is_string( $typeId ) || $typeId === ''
				|| !is_string( $valueType ) || $valueType === ''
			) {
				throw new InvalidArgumentException(
					'$valueTypes must be an associative array of non-empty strings'
				);
			}
		}

		$this->valueTypes = $valueTypes;
	}

	/**
	 * @since 0.1
	 *
	 * @param DataType[] $dataTypes
	 *
	 * @return self
	 */
	public static function newFromTypes( array $dataTypes ) {
		$factory = new self( [] );

		foreach ( $dataTypes as $dataType ) {
			$factory->registerDataType( $dataType );
		}

		return $factory;
	}

	/**
	 * @since 0.1
	 *
	 * @param DataType $dataType
	 */
	public function registerDataType( DataType $dataType ) {
		$this->types[$dataType->getId()] = $dataType;
	}

	/**
	 * Returns the list of registered data type identifiers (also referred to as "property types").
	 *
	 * @since 0.1
	 *
	 * @return string[]
	 */
	public function getTypeIds() {
		return array_keys( $this->valueTypes );
	}

	/**
	 * Returns the data type that has the specified type identifier.
	 * Types may be instantiated on the fly using a type builder spec.
	 *
	 * @since 0.1
	 *
	 * @param string $typeId Data type identifier (also referred to as "property type").
	 *
	 * @throws OutOfBoundsException if the requested type is not known.
	 * @return DataType
	 */
	public function getType( $typeId ) {
		if ( !array_key_exists( $typeId, $this->types ) ) {
			if ( !array_key_exists( $typeId, $this->valueTypes ) ) {
				throw new OutOfBoundsException( "Unknown data type '$typeId'" );
			}

			$valueType = $this->valueTypes[$typeId];
			$this->types[$typeId] = new DataType( $typeId, $valueType );
		}

		return $this->types[$typeId];
	}

	/**
	 * Returns all data types in an associative array with
	 * the keys being data type identifiers (also referred to as "property types") pointing to their
	 * corresponding data type.
	 *
	 * @since 0.1
	 *
	 * @return DataType[]
	 */
	public function getTypes() {
		$types = [];

		foreach ( $this->getTypeIds() as $typeId ) {
			$types[$typeId] = $this->getType( $typeId );
		}

		return $types;
	}

}
