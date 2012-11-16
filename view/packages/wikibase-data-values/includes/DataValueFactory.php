<?php

namespace DataValues;
use InvalidArgumentException;

/**
 * Factory for DataValue objects.
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
 * @ingroup DataValue
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
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
	protected $values;

	/**
	 * Singleton.
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
	 * @param mixed $data
	 *
	 * @return DataValue
	 */
	public function newDataValue( $dataValueType, $data ) {
		$class = $this->getDataValueClass( $dataValueType );
		return $class::newFromArray( $data );
	}

	/**
	 * Returns the class associated with the provided DataValue type.
	 *
	 * @since 0.1
	 *
	 * @param string $dataValueType
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	protected function getDataValueClass( $dataValueType ) {
		if ( !array_key_exists( $dataValueType, $this->values ) ) {
			throw new InvalidArgumentException( 'Unknown data value type "' . $dataValueType . '" has no associated DataValue class' );
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
