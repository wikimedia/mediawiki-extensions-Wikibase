<?php

namespace Wikibase;
use DataTypes\DataType;
use MWException;

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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyObject extends EntityObject implements Property {

	/**
	 * @since 0.2
	 *
	 * @var DataType|null
	 */
	protected $dataType = null;

	/**
	 * @see Property::getDataType
	 *
	 * @since 0.2
	 *
	 * @return DataType
	 * @throws MWException
	 */
	public function getDataType() {
		if ( $this->dataType === null ) {
			if ( array_key_exists( 'datatype', $this->data ) ) {
				return $this->setDataTypeById( $this->data['datatype'] );
			}
			else {
				throw new MWException( 'The DataType of the property is not known' );
			}
		}
		else {
			return $this->dataType;
		}
	}

	/**
	 * @see Property::setDataType
	 *
	 * @since 0.2
	 *
	 * @param DataType $dataType
	 */
	public function setDataType( DataType $dataType ) {
		$this->dataType = $dataType;
	}

	/**
	 * @see Property::setDataTypeById
	 *
	 * @since 0.2
	 *
	 * @param string $dataTypeId
	 *
	 * @return DataType
	 * @throws MWException
	 */
	public function setDataTypeById( $dataTypeId ) {
		if ( is_string( $dataTypeId ) && in_array( $dataTypeId, Settings::get( 'dataTypes' ) ) ) {
			$dataType = \DataTypes\DataTypeFactory::singleton()->getType( $dataTypeId );

			if ( $dataType !== null ) {
				$this->setDataType( $dataType );
				return $dataType;
			}
		}

		throw new MWException( 'The DataType of the property is not valid' );
	}

	/**
	 * @see Entity::toArray
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function toArray() {
		$data = parent::toArray();

		if ( is_null( $this->dataType ) ) {
			if ( array_key_exists( 'datatype', $data ) ) {
				unset( $data['datatype'] );
			}
		}
		else {
			$data['datatype'] = $this->dataType->getId();
		}

		return $data;
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
	 * @see Entity::getDiff
	 *
	 * @since 0.1
	 *
	 * @param Entity $target
	 *
	 * @return PropertyDiff
	 */
	public function getDiff( Entity $target ) {
		// TODO
		return ItemDiff::newEmpty();
	}


	/**
	 * @see EntityObject::getIdPrefix
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public static function getIdPrefix() {
		return Settings::get( 'propertyPrefix' );
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

}
