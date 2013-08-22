<?php

namespace Wikibase;

use DataTypes\DataType;
use DataValues\DataValue;
use InvalidArgumentException;
use RuntimeException;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Represents a single Wikibase property.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Properties
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Property extends Entity {

	const ENTITY_TYPE = 'property';

	/**
	 * @since 0.2
	 *
	 * @var DataType|null
	 */
	protected $dataType = null;

	/**
	 * @since 0.4
	 *
	 * @param string $dataTypeId
	 *
	 * @throws InvalidArgumentException
	 */
	public function setDataTypeId( $dataTypeId ) {
		if ( !is_string( $dataTypeId ) ) {
			throw new InvalidArgumentException( '$dataTypeId needs to be a string' );
		}

		$this->data['datatype'] = $dataTypeId;
	}

	/**
	 * @since 0.4
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function getDataTypeId() {
		if ( array_key_exists( 'datatype', $this->data ) ) {
			assert( is_string( $this->data['datatype'] ) );
			return $this->data['datatype'];
		}

		throw new RuntimeException( 'Cannot obtain the properties DataType as it has not been set' );
	}

	/**
	 * @see Entity::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return Property::ENTITY_TYPE;
	}

	/**
	 * @see Entity::newFromArray
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return Property
	 */
	public static function newFromArray( array $data ) {
		return new static( $data );
	}

	/**
	 * @since 0.1
	 *
	 * @return Property
	 */
	public static function newEmpty() {
		return self::newFromArray( array() );
	}

	/**
	 * @since 0.3
	 *
	 * @param string $dataTypeId
	 *
	 * @return Property
	 */
	public static function newFromType( $dataTypeId ) {
		return self::newFromArray( array( 'datatype' => $dataTypeId ) );
	}

	/**
	 * @since 0.5
	 *
	 * @param string $idSerialization
	 *
	 * @return EntityId
	 */
	protected function idFromSerialization( $idSerialization ) {
		return new PropertyId( $idSerialization );
	}

}
