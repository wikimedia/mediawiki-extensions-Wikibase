<?php

namespace DataValues;
use InvalidArgumentException;

/**
 * Class representing a numeric value with associated unit and accuracy.
 *
 * For simple numeric values use @see NumberValue.
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
class QuantityValue extends DataValueObject {

	/**
	 * @since 0.1
	 *
	 * @var int|float
	 */
	protected $value;

	/**
	 * @since 0.1
	 *
	 * @var string|null
	 */
	protected $unit;

	/**
	 * @since 0.1
	 *
	 * @var int|float|null
	 */
	protected $accuracy;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param int|float $value
	 * @param string|null $unit
	 * @param int|float|null $accuracy
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $value, $unit = null, $accuracy = null ) {
		if ( !is_int( $value ) && !is_float( $value ) ) {
			throw new InvalidArgumentException( 'Can only construct QuantityValue from floats or integers' );
		}

		if ( $accuracy !== null && !is_int( $accuracy ) && !is_float( $accuracy ) ) {
			throw new InvalidArgumentException( 'The accuracy of a QuantityValue needs to be a float or integer' );
		}

		if ( $unit !== null && !is_string( $unit ) ) {
			throw new InvalidArgumentException( 'The unit of a QuantityValue needs to be a string' );
		}

		$this->value = $value;
		$this->unit = $unit;
		$this->accuracy = $accuracy;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return int|float
	 */
	public function serialize() {
		$data = array( $this->value );

		if ( $this->accuracy !== null || $this->unit !== null ) {
			$data[] = $this->unit;
		}

		if ( $this->accuracy !== null ) {
			$data[] = $this->accuracy;
		}

		return serialize( $data );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $data
	 *
	 * @return NumberValue
	 */
	public function unserialize( $data ) {
		$data = unserialize( $data );

		$value = array_shift( $data );
		$unit = array_shift( $data );
		$accuracy = array_shift( $data );

		$this->__construct( $value, $unit, $accuracy );
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return 'quantity';
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
	 * Returns the value held by this quantity.
	 *
	 * @since 0.1
	 *
	 * @return int|float
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Returns the accuracy held by this quantity.
	 *
	 * @since 0.1
	 *
	 * @return int|float|null
	 */
	public function getAccuracy() {
		return $this->accuracy;
	}

	/**
	 * Returns the unit held by this quantity.
	 *
	 * @since 0.1
	 *
	 * @return string|null
	 */
	public function getUnit() {
		return $this->unit;
	}

}
