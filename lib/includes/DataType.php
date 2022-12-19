<?php

namespace Wikibase\Lib;

use InvalidArgumentException;

/**
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DataType {

	/**
	 * Identifier of this data type (also referred to as "property type").
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $typeId;

	/**
	 * Identifier for the type of the DataValue.
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $dataValueType;

	/**
	 * @since 0.5
	 *
	 * @param string $typeId Identifier of this data type (also referred to as "property type").
	 * @param string $dataValueType Identifier for the type of the DataValue.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $typeId, $dataValueType ) {
		if ( !is_string( $typeId ) || $typeId === '' ) {
			throw new InvalidArgumentException( '$typeId must be a non-empty string' );
		}

		if ( !is_string( $dataValueType ) || $dataValueType === '' ) {
			throw new InvalidArgumentException( '$dataValueType must be a non-empty string' );
		}

		$this->typeId = $typeId;
		$this->dataValueType = $dataValueType;
	}

	/**
	 * Returns the identifier of this data type (also referred to as "property type").
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getId() {
		return $this->typeId;
	}

	/**
	 * Returns the type of the DataValue used by this data type.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getDataValueType() {
		return $this->dataValueType;
	}

	/**
	 * @since 1.0
	 *
	 * @return string
	 */
	public function getMessageKey() {
		return 'datatypes-type-' . $this->typeId;
	}

	/**
	 * @since 0.1
	 *
	 * @return string[]
	 */
	public function toArray() {
		return [
			'dataValueType' => $this->dataValueType,
		];
	}

}
