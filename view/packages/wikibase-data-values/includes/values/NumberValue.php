<?php

namespace DataValues;
use InvalidArgumentException;

/**
 * Class representing a simple numeric value.
 *
 * More complex numeric values that have associated info such as
 * unit and accuracy can be represented with a @see QuantityValue.
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
class NumberValue extends DataValueObject {

	/**
	 * @since 0.1
	 *
	 * @var int|float
	 */
	protected $value;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param int|float $value
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $value ) {
		if ( !is_int( $value ) && !is_float( $value ) ) {
			throw new InvalidArgumentException( 'Can only construct NumberValue from floats or integers' );
		}

		$this->value = $value;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return int|float
	 */
	public function serialize() {
		return serialize( $this->value );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @return NumberValue
	 */
	public function unserialize( $value ) {
		$this->__construct( unserialize( $value ) );
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return 'number';
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

	/**
	 * Returns the number.
	 * DataValue::getValue
	 *
	 * @since 0.1
	 *
	 * @return int|float
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Constructs a new instance of the DataValue from the provided data.
	 * This can round-trip with @see getArrayValue
	 *
	 * @since 0.1
	 *
	 * @param mixed $data
	 *
	 * @return DataValue
	 */
	public static function newFromArray( $data ) {
		return new static( $data );
	}

}
