<?php

namespace Wikibase;

use DataTypes\DataType;
use DataValues\DataValue;
use InvalidArgumentException;
use RuntimeException;

/**
 * Represents a single Wikibase property.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Properties
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
	 * Returns the DataType of the property.
	 *
	 * @since 0.2
	 * @deprecated since 0.4
	 *
	 * @return DataType
	 * @throws InvalidArgumentException
	 */
	public function getDataType() {
		if ( $this->dataType === null ) {
			if ( array_key_exists( 'datatype', $this->data ) ) {
				$registry = new LibRegistry( Settings::singleton() );

				$this->dataType = $registry->getDataTypeFactory()->getType( $this->data['datatype'] );

				if ( $this->dataType === null ) {
					throw new InvalidArgumentException( 'The DataType of the property is not valid' );
				}
			}
			else {
				throw new InvalidArgumentException( 'The DataType of the property is not known' );
			}
		}

		return $this->dataType;
	}

	/**
	 * Sets the DataType of the property.
	 *
	 * @since 0.2
	 * @deprecated since 0.4
	 *
	 * @param DataType $dataType
	 */
	public function setDataType( DataType $dataType ) {
		$this->dataType = $dataType;
		$this->setDataTypeId( $dataType->getId() );
	}

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
	 * Factory for creating new DataValue objects for the property.
	 *
	 * @since 0.3
	 * @deprecated since 0.4
	 *
	 * @param mixed $rawDataValue
	 *
	 * @return DataValue
	 */
	public function newDataValue( $rawDataValue ) {
		return \DataValues\DataValueFactory::singleton()->newDataValue( $this->getDataType()->getDataValueType(), $rawDataValue );
	}

}
