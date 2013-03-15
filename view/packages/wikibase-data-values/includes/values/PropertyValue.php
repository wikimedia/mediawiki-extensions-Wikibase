<?php

namespace DataValues;

use InvalidArgumentException;

/**
 * Class representing the identity of a property.
 *
 * Loosely based on SMWDIProperty.
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
class PropertyValue extends DataValueObject {

	/**
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param string $propertyId
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $propertyId ) {
		if ( !is_string( $propertyId ) ) {
			throw new InvalidArgumentException( 'Can only construct PropertyValue with a string id' );
		}

		$this->id = $propertyId;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return int|float
	 */
	public function serialize() {
		return json_encode( array( $this->id ) );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @return NumberValue
	 * @throws InvalidArgumentException
	 */
	public function unserialize( $value ) {
		$data = json_decode( $value );

		if ( !is_array( $data ) || !array_key_exists( 0, $data ) ) {
			throw new InvalidArgumentException( 'Invalid serialization provided' );
		}

		$this->__construct( $data[0] );
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return 'property';
	}

	/**
	 * @see DataValue::getSortKey
	 *
	 * @since 0.1
	 *
	 * @return string|float|int
	 */
	public function getSortKey() {
		return $this->id;
	}

	/**
	 * @see DataValue::getArrayValue
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function getArrayValue() {
		return array(
			'propertyId' => $this->id,
		);
	}

	/**
	 * @see DataValue::getValue
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function getValue() {
		return $this;
	}

	/**
	 * Constructs a new instance of the DataValue from the provided data.
	 * This can round-trip with @see getArrayValue
	 *
	 * @since 0.1
	 *
	 * @param mixed $data
	 *
	 * @return PropertyValue
	 * @throws InvalidArgumentException
	 */
	public static function newFromArray( $data ) {
		if ( !is_array( $data ) || !array_key_exists( 'propertyId', $data ) ) {
			throw new InvalidArgumentException( 'Cannot construct instance from invalid array format' );
		}

		return new static( $data['propertyId'] );
	}

}
